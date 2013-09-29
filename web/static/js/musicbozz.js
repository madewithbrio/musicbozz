
window.fbAsyncInit = function() {
      FB.init({
        appId      : '667899479901883', // App ID
        channelUrl : '//vmdev-musicbozz.vmdev.bk.sapo.pt/channel.html', // Channel File
        status     : true, // check login status
        cookie     : true, // enable cookies to allow the server to access the session
        xfbml      : true  // parse XFBML
      });
}

var musicbozz = (function(facebookSDK){
	'use strict';
	var roomInstance, 
	    convertDecimalToSec = function(decimal) {
		var secs  = Math.floor(decimal) || 0;
  		if (secs < 10) secs = "0" + secs;
  		return secs;
	    },
	    player = new MediaElementPlayer('#player', {
		type: 'audio/mp3',
		success: function(media, node, player) {
			media.addEventListener('timeupdate', function(e){
				//console.log(this);
				var played = 0, $scrubber = $('#scrubber');
				if ((media.currentTime != undefined)) {
				    played = parseInt((100 - (media.currentTime / media.duration) * 100), 10) || 0;
				    $scrubber.find('.remaining-time').css({width: played + '%'});
				    $scrubber.find('.timer').html(convertDecimalToSec(media.duration - media.currentTime));
				}
			});
			media.addEventListener('canplay', function() {
				var hash = $('div[data-template="question"] .query').attr('data-hash');
				service.setReadyToPlay(roomInstance, hash);
			});
			media.addEventListener('ended', function() { 
				var hash = $('div[data-template="question"] .query').attr('data-hash');
				service.notifyTimeEnded(roomInstance, hash);
			});

		}
	});

	var Room = (function() {
		var Room = function(player, type, roomName) {
			if (typeof type === 'undefined' || !/(alone|private|public)/.test(type)) type = 'alone';
			this.type = type;
			this.player = player;
			this.players = [];
			this.question = undefined;
			this.roomName = roomName || this.player.username;
			this.master = !roomName;
		}

		Room.prototype.addPlayer = function() {}
		Room.prototype.setMaster = function(master) { this.master; }
		Room.prototype.getPlayer = function() { return this.player; }
		Room.prototype.getQuestion = function() {}
		Room.prototype.isAlone = function() { return this.type == 'alone'; }
		Room.prototype.isMaster = function () { return this.master; }
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
		var 	view = {}, 
			partialTemplates = [],			
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
			player.setSrc(data.url);
			player.load();
		};

		view.renderPlayersAnswerResult = function(data) {
			var $playerContainer = $('a[data-player-id="'+data.player.id+'"]'),
				$pointsContainer = $playerContainer.find('[data-element="points"]'),
				clazzName = data.questionScore > 0 ? 'positive' : 'negative';

			if (data.questionScore == 0) clazzName = ""; // hack because 0 
			$pointsContainer.html(data.totalScore);
			$pointsContainer.addClass(clazzName).addClass('active'); //.html(data.questionScore);
		};

		view.cleanPlayesrAnswerNotifications = function() {
			var $playerContainer = $('a[data-player-id]');
			$playerContainer.find('p.total-points').removeClass('active positive negative');
		};

		view.renderPlayerAnswer = function(data) {
			var clazzName;
			if (null == data.res) return;
			
			clazzName =  data.res ? 'correct' : 'wrong';
			this.liElement.children().addClass('selected ' + clazzName);
			this.liElement.parent().addClass('has_answer');
		}

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
		var controller = {}, hasAnswer = false, timeoutQuestion,
			errorHandling = function(error, desc){ 
				console.error(error, desc) 
			};

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
				//view.renderQuestion(question);
			}, errorHandling);
		}

		controller.newQuestion = function(question) {
			hasAnswer = false;
			view.renderQuestion(question);
		}

		controller.startAudioPlayer = function() {
			console.log("start");
			setTimeout( function() { 
				console.log(player);
				player.play(); 
			}, 250);
		}

		controller.playersAnswerResult = function(result) {
			view.renderPlayersAnswerResult(result);
		}

		controller.questionOver = function() {
			player.pause();
			clearTimeout(timeoutQuestion);
			view.cleanPlayesrAnswerNotifications();
			if(roomInstance.isMaster()) service.getNewQuestion(roomInstance);
		//	if (result.isOver) { $('div.question').html(""); }
		}

		controller.setAnswer = function(el) {
			var $li = $(el).parent(), hash = $('div[data-template="question"] .query').attr('data-hash'),
				answer = $li.parent().find('li').index($li);
			if (hasAnswer) return;
			hasAnswer = true; 
			player.pause();
			$('a[data-element="answer"]').unbind('click.answer');

			service.setAnswer(roomInstance, answer, hash, view.renderPlayerAnswer.bind({liElement: $li}));
		}

		controller.eventHandler = function(t, e) {
			switch (e.action) {
				case 'playerConfigChange':
				case 'newPlayer':
				case 'playerLeave':
					service.listPlayers(roomInstance, view.renderPlayers , errorHandling);
					break;
			
				case 'newQuestion':
					//service.getNewQuestion(roomInstance, view.renderQuestion , errorHandling);
					//renderQuestion(e.data);
					controller.newQuestion(e.data);
					timeoutQuestion = setTimeout( controller.questionOver, 45000);
					break;

				case 'allPlayersReady':
					controller.startAudioPlayer();
					break;

				case 'playersAnswerResult':
					controller.playersAnswerResult(e.data);
					break;

				case 'allPlayersAllreadyResponde':
					controller.questionOver();
					break;

				case 'setMaster':
					roomInstance.setMaster(true);
					break;
				case 'gameOver':
					console.log("game over");
					break;
					
				default:
					break;
			}
		}

		// bind gui components
		// prevent default all link and submit actions
		$(document).delegate('a', 'click', function(e){e.preventDefault();});
		$(document).delegate('form', 'submit', function(e){e.preventDefault();});

		$('a[data-action="start"]').bind('click', function(e){
			e.preventDefault();
			controller.goRoom(this.getAttribute('data-game-type'), this.getAttribute('data-room-name'));
		});

		$(document).delegate('a[data-element="answer"]', 'click.answer', function(e){
			e.preventDefault();
			controller.setAnswer(this);
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
			
			ws_session.call(gameRoom.getRoomId(), 'getNewQuestion').then(onSuccess, onError);
		}

		service.setReadyToPlay = function (gameRoom, hash, onSuccess, onError) {
			if (typeof onSuccess !== 'function') onSuccess = function(){};
			if (typeof onError !== 'function') onError = function(){};
			
			ws_session.call(gameRoom.getRoomId(), 'setReadyToPlay', {hash: hash}).then(onSuccess, onError);
		}

		service.notifyTimeEnded = function (gameRoom, hash, onSuccess, onError) {
			if (typeof onSuccess !== 'function') onSuccess = function(){};
			if (typeof onError !== 'function') onError = function(){};
			
			ws_session.call(gameRoom.getRoomId(), 'timeEnded', {answer: null, hash: hash}).then(onSuccess, onError);
		}

		service.setAnswer = function (gameRoom, answer, hash, onSuccess, onError) {
			if (typeof onSuccess !== 'function') onSuccess = function(){};
			if (typeof onError !== 'function') onError = function(){};

			ws_session.call(gameRoom.getRoomId(), 'setAnswer', {answer: answer, hash: hash}).then(onSuccess, onError);
		}

		
		return service;
	})();

	return {
		getPlayer: function() {
			return player;
		},
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
