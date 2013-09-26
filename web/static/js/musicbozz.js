
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

	// events
	$('a[data-action="start"]').bind('click', function(e){
		e.preventDefault();
		controller.goRoom(this.getAttribute('data-game-type'));
	});



	$(document).ready(function(){

	});

	var view = (function(){
		var view = {}, partialTemplates = [], $room = $('#room'), $homepage = $('#homepage');
		view.showRoom = function() {
			$room.show();
			$homepage.hide();
		}

		view.showHomepage = function() {
			$homepage.show();
			$room.hide();
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
		loadTemplates();
		return view;		
	})();

	var Room = (function() {
		var Room = function(player, type) {
			if (typeof type === 'undefined' || !/(alone|private|public)/.test(type)) type = 'alone';
			this.type = type;
			this.player = player;
			this.players = [];
			this.master = false;
			this.question = undefined;
		}

		Room.prototype.addPlayer = function() {}
		Room.prototype.setMaster = function(master) { this.master; }
		Room.prototype.getPlayer = function() { return this.player; }
		Room.prototype.getQuestion = function() {}
		Room.prototype.getRoomId = function() {
			return ((this.type == 'alone') ? 'alone/' : 'room/')
				 + ((this.type !== 'public') ? this.player.username : '1');
		}
		Room.prototype.getLocation = function() {
			return 'ws://vmdev-musicbozz.vmdev.bk.sapo.pt/ws/'
				 + this.getRoomId();
		}

		return Room;
	})();

	var controller = (function() {
		var controller = {};
		controller.goRoom = function(type) {
			facebookSDK.getLoginStatus(function(response) {
			  	if (response.status === 'connected') {
			  		service.loadFacebookPersona(function(){
			  			roomInstance = new Room(service.getPlayer(), type);
				    	view.showRoom();
				    	service.connect(roomInstance);
			  		});
			  	} else if (response.status === 'not_authorized') {
			    	controller.login(type);
			  	} else {
			    	controller.login(type);
			  	}
			});
		}

		controller.login = function(type) {
			facebookSDK.Event.subscribe('auth.authResponseChange', function(response) {
		        if (response.status === 'connected') {
		          service.loadFacebookPersona(function(){
		          	controller.goRoom(type);
		          });
		        }
		  	});
		  	facebookSDK.login();
		}

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
				session.subscribe(gameRoom.getRoomId(),ws_events);
				session.call(gameRoom.getRoomId(), 'setPlayer', gameRoom.getPlayer());
			}, function(){
				console.log("session closed");
			});
		}

		service.listPlayers = function(gameRoom) {
			ws_session.call(gameRoom.getRoomId(), 'listPlayers').then(view.renderPlayers, function(error, desc){ console.error(error, desc); });
		}

		var ws_events = function(t, e) {
			switch (e.action) {
				case 'playerConfigChange':
				case 'newPlayer':
					service.listPlayers(roomInstance);
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
					roomInstance.setMaster(true);
					break;

				case 'playerLeave':
					listPlayers();
					break;

				default:
					break;
			}
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