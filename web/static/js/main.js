
var musicbozz = (function(){
	var sess, wsuri = "ws://62.28.238.103:9000", gameRoom, partialTemplates = {}, master = null;

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
		var player = $("#player").get(0)
		player.play();
		$(player).bind('ended.player', function() { 
			sess.call(gameRoom, 'timeEnded');
		});
	};

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

		$(document).delegate('a[data-element="answer"]', 'click', function(e){
			var $li = $(this).parent();
			var answer = $li.parent().find('li').index($li);
			sess.call(gameRoom, 'setAnswer', answer);
		});

		// prevent default all link and submit actions
		$(document).delegate('a', 'click', function(e){e.preventDefault();});
		$(document).delegate('form', 'submit', function(e){e.preventDefault();});
	});

	return {};
})();
