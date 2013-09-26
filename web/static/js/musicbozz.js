
window.fbAsyncInit = function() {
      FB.init({
        appId      : '667899479901883', // App ID
        channelUrl : '//musicbozz.local/channel.html', // Channel File
        status     : true, // check login status
        cookie     : true, // enable cookies to allow the server to access the session
        xfbml      : true  // parse XFBML
      });
}

var musicbozz = (function(facebookSDK){
	'use strict';
	var roomInstance;

	var Room = (function() {
		var Room = function(player, type, roomName) {
			if (typeof type === 'undefined' || !/(alone|private|public)/.test(type)) type = 'alone';
			this.type = type;
			this.player = player;
			this.players = [];
			this.master = false;
			this.question = undefined;
			this.roomName = roomName || this.player.username;
		}

		Room.prototype.addPlayer = function() {}
		Room.prototype.setMaster = function(master) { this.master; }
		Room.prototype.getPlayer = function() { return this.player; }
		Room.prototype.getQuestion = function() {}
		Room.prototype.isAlone = function() {
			return this.type == 'alone';
		}
		Room.prototype.getRoomId = function() {
			return ((this.type == 'alone') ? 'alone/' : 'room/') + this.roomName;
		}
		Room.prototype.getLocation = function() {
			return 'ws://vmdev-musicbozz.vmdev.bk.sapo.pt/ws/'
				 + this.getRoomId();
		}

		return Room;
	})();

	var view = (function(){
		var view = {}, partialTemplates = [], 
			$room = $('#room'), 
			$homepage = $('#homepage');

		view.showRoom = function(alone) {
			if (alone) $room.addClass('alone');
			$room.show();
			$homepage.hide();
		}

		view.showHomepage = function() {
			$homepage.show();
			$room.hide();
		}

		view.renderGamestart = function() {
			$('#standBy').hide();
			$('#gameBoard').show();
		}

		view.renderPlayers = function(res) {
	        $('ul[data-template="players"]').html(Mustache.render(getTemplate('players'), {players: res}, getTemplate()));
	        /**
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
	        **/
		};

		view.renderQuestion = function(data) {
			$('div[data-template="question"]').html(Mustache.render(getTemplate('question'), data));
			var player = $("#player").get(0);
			$(player).children().attr('src', data.url);
			player.load();
		};

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

		var loadTemplates = function() { //@todo
			var templates = document.querySelectorAll('script[data-element="template"][type="text/mustache"]');
			templates.forEach = [].forEach;
			templates.forEach(function(element) {
				var name = element.getAttribute('data-name');
				partialTemplates[name] = element.innerHTML;
			});
		};

		var onLoadHomepage = function() {
			var room = /room=(.+)/.exec(window.location.hash);
			if (!room) return undefined;
			$("#joinPrivateRoom").find('a').attr('data-room-name', room);
			$("#joinPrivateRoom").show();
			$("#startPrivateRoom").hide();
		};

		loadTemplates();
		onLoadHomepage();
		return view;		
	})();

	var controller = (function() {
		var controller = {},
			errorHandling = function(error, desc){ console.error(error, desc) };

		controller.goRoom = function(type, roomName) {
			facebookSDK.getLoginStatus(function(response) {
			  	if (response.status === 'connected') {
			  		service.loadFacebookPersona(function(){
			  			roomInstance = new Room(service.getPlayer(), type, roomName);
				    	view.showRoom(roomInstance.isAlone());
				    	service.connect(roomInstance);
			  		});
			  	} else if (response.status === 'not_authorized') {
			    	controller.login(type, roomName);
			  	} else {
			    	controller.login(type, roomName);
			  	}
			});
		}

		controller.login = function(type, roomName) {
			facebookSDK.Event.subscribe('auth.authResponseChange', function(response) {
		        if (response.status === 'connected') {
		          service.loadFacebookPersona(function(){
		          	controller.goRoom(type, roomName);
		          });
		        }
		  	});
		  	facebookSDK.login();
		}

		controller.startGame = function() {
			service.getNewQuestion(roomInstance, function(question) {
				view.renderGamestart();
				view.renderQuestion(question);
			}, errorHandling);
		}

		controller.newQuestion = function(question) {
			view.renderQuestion(question);
		}

		controller.eventHandler = function(t, e) {
			switch (e.action) {
				case 'playerConfigChange':
				case 'newPlayer':
					service.listPlayers(roomInstance, view.renderPlayers , errorHandling);
					break;
			
				case 'newQuestion':
					//service.getNewQuestion(roomInstance, view.renderQuestion , errorHandling);
					//renderQuestion(e.data);
					controller.newQuestion(e.data);
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
					roomInstance.setMaster(true);
					break;

				case 'playerLeave':
					listPlayers();
					break;

				default:
					break;
			}
		}

		// bind gui components
		$('a[data-action="start"]').bind('click', function(e){
			e.preventDefault();
			controller.goRoom(this.getAttribute('data-game-type'), this.getAttribute('data-room-name'));
		});

		return controller;
	})();

	var service = (function() {
		var service = {}, ws_session, playerConfig;
		service.getPlayer = function() {
			return playerConfig;
		}

		service.loadFacebookPersona = function(onResponseClb) {
			facebookSDK.api('/me', function(response) {
	          console.log(response);
	          playerConfig = {
	          	name: response.first_name,
	          	avatar: 'http://graph.facebook.com/'+response.username+'/picture',
	          	username: response.username,
	          	link: response.link
	          }
	          if (typeof onResponseClb === 'function') onResponseClb.apply(null, playerConfig);
	        });
		}

		service.connect = function(gameRoom) {
			ab.connect(gameRoom.getLocation(), function(session){
				ws_session = session;
				console.log("session open");
				session.subscribe(gameRoom.getRoomId(),controller.eventHandler);
				session.call(gameRoom.getRoomId(), 'setPlayer', gameRoom.getPlayer());
				if (gameRoom.isAlone()) {
					controller.startGame();
				}
			}, function(){
				console.log("session closed");
			});
		}

		service.listPlayers = function(gameRoom, onSuccess, onError) {
			if (typeof onSuccess !== 'function') onSuccess = function(){};
			if (typeof onError !== 'function') onError = function(){};
			
			ws_session.call(gameRoom.getRoomId(), 'listPlayers').then(onSuccess, onError);
		}

		service.getNewQuestion = function (gameRoom, onSuccess, onError) {
			if (typeof onSuccess !== 'function') onSuccess = function(){};
			if (typeof onError !== 'function') onError = function(){};
			
			ws_session.call(gameRoom.getRoomId(), 'newQuestion').then(onSuccess, onError);
		}

		
		return service;
	})();

	return {
		getRoom: function() {
			return roomInstance;
		},

		getController: function() {
			return controller;
		},

		getService: function() {
			return service;
		},

		getView: function() {
			return view;
		}
	}
})(FB);