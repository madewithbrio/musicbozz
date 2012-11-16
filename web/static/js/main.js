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
	var sess, wsuri = "ws://localhost:9000", gameRoom, partialTemplates = {}, master = null;

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
		gameRoom = "http://localhost/game/"+room;
		sess.subscribe(gameRoom,onEvent);
	};

	var listPlayers = function() {
		sess.call(gameRoom, 'listPlayers').then(renderPlayersList, renderError);
	};

	var renderPlayersList = function(res) {
		if (null == master) { master = typeof res[1].name == 'undefined'; }
        $('ul[data-template="players"]').html(Mustache.render(getTemplate('players'), {players: res}, getTemplate()));
        if (!master) {
        	$('#start_game').hide();
        } else {
        	if (typeof res[1].name !== 'undefined') {
        		$('#start_game .go').removeClass('inactive');	
        	} else {
        		$('#start_game .go').addClass('inactive');	
        	}
        }
	};

	var renderQuestion = function(data) {
		$('div[data-template="question"]').html(Mustache.render(getTemplate('question'), data));
		var player = $("#player").get(0);

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
	};

	var renderPlayerAnswer = function(data) {
		var $playerContainer = $('a[data-player-id="'+data.player.id+'"]');
		$playerContainer.find('p.total_points').html(data.totalScore);
		var clazzName = data.questionScore > 0 ? 'positive' : 'negative';
		$playerContainer.find('p.score').addClass(clazzName).addClass('active').html(data.questionScore);
	};

	var resetPlayerAnswer = function() {
		setTimeout((function(){
			var $playerContainer = $('a[data-player-id]');
			$playerContainer.find('p.score').removeClass('active positive negative');
			if (master) { this.ws.call(gameRoom, 'newQuestion'); }
			}).bind({ws: sess})
		,1000);
	}

	var getInviteRoom = function() {
		var room = /invite=(\d+)/.exec(window.location.hash);
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
				resetPlayerAnswer();
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
		var room;

		loadTemplates();
		connect();

		room = getInviteRoom();
		if (typeof room !== 'undefined') {
			$('section.start.by_invitation').addClass('active');
		} else {
			$('section.start.new_game').addClass('active');
		}

		$('input[type="submit"][data-action="join"]').bind('click', function(e){
			var $form 		= $(this).parent(), 
				action 		= $form.attr('action'),
				playerName 	= $form.find('input[name="name"]').val();

			if (sess == null) {
				alert("Sorry! You're connected to the server, whatever that means...");
				return;
			}

			if (action == "#joinRoom" && !!room) { joinGame(room,eventListener); }
			else { joinGame(undefined, eventListener); }

			if (playerName != "") {
				sess.call(gameRoom, 'setPlayerName', playerName);
			}
			startApp();
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
			$('#share #room_link').select();
		});

		$(document).delegate('a[data-element="answer"]', 'click', function(e){
			var $li = $(this).parent();
			var answer = $li.parent().find('li').index($li);
			//$("#player").get(0).pause();
			sess.call(gameRoom, 'setAnswer', answer).then(function(data){
				var clazzName = data.res ? 'correct' : 'wrong';
				$li.children().addClass('selected ' + clazzName);
				$li.parent().addClass('has_answer');
			}, renderError);
		});

		// prevent default all link and submit actions
		$(document).delegate('a', 'click', function(e){e.preventDefault();});
		$(document).delegate('form', 'submit', function(e){e.preventDefault();});
	});

	return {};
})();
