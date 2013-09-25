if (!Function.prototype.bind ) {
	Function.prototype.bind = function( obj ) {
		var slice = [].slice,
				args = slice.call(arguments, 1), 
				self = this, 
				nop = function () {}, 
				bound = function () {
					return self.apply( this instanceof nop ? this : ( obj || {} ), 
															args.concat( slice.call(arguments) ) );
				};

		nop.prototype = self.prototype;

		bound.prototype = new nop();

		return bound;
	};
}

//"ws://62.28.238.103:9000"
var musicbozz = (function(){
	var sess, wsuri = "ws://vmdev-musicbozz.vmdev.bk.sapo.pt/ws/", gameRoom, partialTemplates = {}, master = null;

	var convertDecimalToMinSec = function(decimal) {
		var hours = Math.floor(decimal/3600,10),
			mins  = Math.floor((decimal - hours*60)/60,10),
  		    secs  = Math.floor(decimal - mins*60);
  		if (mins < 10) mins = "0" + mins;  
  		if (secs < 10) secs = "0" + secs;
  		if (hours > 0) mins = hours + ":" + mins;
  		return mins+":"+secs;
	};

	var getTemplate = function(name) {
		if (typeof name === "string" && typeof partialTemplates[name] === "string") { return partialTemplates[name] }
		else if (typeof name === "string") throw "template not defined";
		return partialTemplates;
	};

	var loadTemplates = function() {
		var templates = document.querySelectorAll('script[data-element="template"][type="text/mustache"]');
		templates.forEach = [].forEach;
		templates.forEach(function(element) {
			var name = element.getAttribute('data-name');
			partialTemplates[name] = element.innerHTML;
		});
	};

	var connect = function() {
	  ab.connect(wsuri,
	     function (session) {
	        sess = session;
	        console.log("Connected to " + wsuri);
	     },
	     function (code, reason) {
	        sess = null;
	        console.log("Connection lost (" + reason + ")");
	     }
	  );
	};

	var renderError = function (error, desc) { console.log("error: " + desc); }

	var joinGame = function(room, onEvent) {
		if (typeof room === "undefined") { room = Math.floor(Math.random()*11); }
		console.log("join to game room: " + room);
	//	gameRoom = "http://localhost/game/"+room;
		sess.subscribe(room.toString(),onEvent);
		$('.room_id').html(room);
		location.hash = "#room="+room;
	};

	var listPlayers = function() {
		sess.call(gameRoom, 'listPlayers').then(renderPlayersList, renderError);
	};

	var renderPlayersList = function(res) {
        $('ul[data-template="players"]').html(Mustache.render(getTemplate('players'), {players: res}, getTemplate()));
        if (!master) {
        	$('#start_game').hide();
        	$('#stand_by').show();
        } else {
        	$('#stand_by').hide();
        	if (typeof res[1].name !== 'undefined') {
        		$('#start_game .go').removeClass('inactive');	
        	} else {
        		$('#start_game .go').addClass('inactive');	
        	}
        }
	};

	var renderQuestion = function(data) {
		$("#stand_by").hide();
		$('div[data-template="question"]').html(Mustache.render(getTemplate('question'), data));
		var player = $("#player").get(0);
		$(player).children().attr('src', data.url);
		player.load();
	};

	var renderPlayerAnswer = function(data) {
		var $playerContainer = $('a[data-player-id="'+data.player.id+'"]');
		$playerContainer.find('p.total_points').html(data.totalScore);
		var clazzName = data.questionScore > 0 ? 'positive' : 'negative';
		if (data.questionScore == 0) clazzName = ""; // hack because 0 
		$playerContainer.find('p.score').addClass(clazzName).addClass('active').html(data.questionScore);
	};

	var resetPlayerAnswer = function(data) {
		setTimeout((function(){
			var $playerContainer = $('a[data-player-id]');
			$playerContainer.find('p.score').removeClass('active positive negative');
			if (master && !this.isOver) { this.ws.call(gameRoom, 'newQuestion'); }
			if (this.isOver) { $('div.question').html(""); }
			}).bind({ws: sess, isOver: data.over})
		,1000);
	}

	var getInviteRoom = function() {
		var room = /room=(\d+)/.exec(window.location.hash);
		if (!room) return undefined;
		return room[1];
	};

	var eventListener = function (t, e) {
		switch (e.action) {
			case 'playerNameChange':
			case 'newPlayer':
				renderPlayersList(e.data);
				break;
		
			case 'newQuestion':
				renderQuestion(e.data);
				break;

			case 'allPlayersReady':
				$("#player").get(0).play();
				break;

			case 'playerAnswer':
				renderPlayerAnswer(e.data);
				break;

			case 'allPlayersAllreadyResponde':
				$("#player").get(0).pause();
				resetPlayerAnswer(e.data);
				break;

			case 'setMaster':
				master = true;
				break;

			case 'playerLeave':
				listPlayers();
				break;

			default:
				break;
		}
	};

	var startApp = function(){
		$('section.start').removeClass('active');
		$('section.app').addClass('active');
		master = null;
	};

	$(document).ready(function(){
		// connect ws
		var room, playerName, player = $('#player').get(0);

		loadTemplates();

		room = getInviteRoom();
		if (typeof room !== 'undefined') {
			$('.room_id').html('sala ' + room);
			$('section.start.by_invitation').addClass('active');
		} else {
			$('section.start.new_game').addClass('active');
		}

		$(document).delegate('div.song_scrubber', 'touchstart', function(){
			player.play();
		})

		var $joinButton = $('input[type="submit"][data-action="join"]');
		$joinButton.attr('disable', true).bind('click', function(e){
			if (!playerName) return;

			player.play();
			player.pause();

			var $form 		= $(this).parent(), 
				action 		= $form.attr('action');
				//playerName 	= $form.find('input[name="name"]').val();

			if ($form.find('input[name="room"]').length) {
				room 		= $form.find('input[name="room"]').val();
			}

			if (typeof room === "undefined") { room = Math.floor(Math.random()*11); }

			ab.connect(wsuri + room,
			    function (session) {
			        sess = session;
			        console.log("Connected to " + wsuri);
			        joinGame(room,eventListener);
			        sess.call(room, 'setPlayerName', playerName);
			        startApp();
			    },
			    function (code, reason) {
			        sess = null;
			        console.log("Connection lost (" + reason + ")");
			    });
		});

		$("#start_game a").bind('click', function(e) {
			if($('#start_game .go').hasClass('inactive')) {
			} else {
				$('#start_game').hide();
				sess.call(gameRoom, 'newQuestion').then(function() {
					// setTimeout here ?
				}, renderError);
			}
		});

		$(".modal .close").bind('click', function(e) {
			$('.overlay').removeClass('active');
			$('.modal').removeClass('active');
		});

		$(document).delegate('a[href="#share"]', 'click', function(e) {
			//generate room link at this point
			$('.overlay').addClass('active');
			$('.modal').addClass('active');
			$('#share #room_link').val(window.location).select();
		});

		$(document).delegate('a[data-element="answer"]', 'click.answer', function(e){
			$('a[data-element="answer"]').unbind('click.answer');
			var $li = $(this).parent();
			var answer = $li.parent().find('li').index($li);
			sess.call(gameRoom, 'setAnswer', answer).then(function(data){
				if (null == data.res) return;
				var clazzName = data.res ? 'correct' : 'wrong';
				$li.children().addClass('selected ' + clazzName);
				$li.parent().addClass('has_answer');
			}, renderError);
		});

		$(player).bind('timeupdate.player', function(e){
			if ((player.currentTime != undefined)) {
			    played = parseInt((100 - (player.currentTime / player.duration) * 100), 10);
			    scrubber = $('div.song_scrubber');
			    scrubber.find('.remaining_time').css({width: played + '%'});
			    scrubber.find('p.timer').html(convertDecimalToMinSec(player.duration - player.currentTime));
			}
		});
		$(player).bind('canplay.player', function() {
			sess.call(gameRoom, 'setReadyToPlay');
		});
		$(player).bind('ended.player', function() { 
			sess.call(gameRoom, 'timeEnded');
		});


		// prevent default all link and submit actions
		$(document).delegate('a', 'click', function(e){e.preventDefault();});
		$(document).delegate('form', 'submit', function(e){e.preventDefault();});

		FB.Event.subscribe('auth.authResponseChange', function(response) {
            // Here we specify what we do with the response anytime this event occurs. 
            if (response.status === 'connected') {
              // The response object is returned with a status field that lets the app know the current
              // login status of the person. In this case, we're handling the situation where they 
              // have logged in to the app.
              FB.api('/me', function(response) {
	              console.log(response.name);
	              playerName = response.name;
	              $joinButton.removeAttr('disable');
	            });
            } else if (response.status === 'not_authorized') {
              // In this case, the person is logged into Facebook, but not into the app, so we call
              // FB.login() to prompt them to do so. 
              // In real-life usage, you wouldn't want to immediately prompt someone to login 
              // like this, for two reasons:
              // (1) JavaScript created popup windows are blocked by most browsers unless they 
              // result from direct interaction from people using the app (such as a mouse click)
              // (2) it is a bad experience to be continually prompted to login upon page load.
              
              FB.login();
            } else {
              // In this case, the person is not logged into Facebook, so we call the login() 
              // function to prompt them to do so. Note that at this stage there is no indication
              // of whether they are logged into the app. If they aren't then they'll see the Login
              // dialog right after they log in to Facebook. 
              // The same caveats as above apply to the FB.login() call here.
              FB.login();
            }
          });
        
	});

	return {};
})();
