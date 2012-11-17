
var WebSocket = require('faye-websocket');

var now = function() {return (new Date()).getTime(); };

WebClient = function() {

	var interface_public = {}, 
		//ws = new WebSocket.Client('ws://localhost:9000/');
		ws = new WebSocket.Client('ws://62.28.238.103:9000/'),
		timestamp = now(),
		events = [],
		room = null,
		isConnected = false,
		isSubscribed = false;

	ws.client = interface_public;

	ws.onopen = function(event) 
	{
		this.client.setConnected();
//console.log('open');
//	  ws.send('Hello, world!');
	};

	ws.onmessage = function(event)
	{
//console.log('message', event.data);
		var data = JSON.parse(event.data);
		var typeId = data[0];
		if (typeId == 0)
		{
			if (this.client.roomIsSet())
			{
				this.sendSubscribe();
				isSubscribed = true;
			}
		}
		else if (typeId == 3 || typeId == 4 || typeId == 8) 
			this.client.addEvent(data[2]);
	};

	ws.onclose = function(event)
	{
		isConnected = false;
//console.log('close', event.code, event.reason);
		ws = null;
	};

	ws.sendSubscribe = function()
	{
		var subscribeMessage = JSON.stringify([5, this.client.getRoomURL()]);
//console.log("sending subscribe", subscribeMessage);
		this.send(subscribeMessage);
	};

	interface_public.send = function(action, param)
	{
//console.log('try send', action, isConnected, this.roomIsSet());
		if ('subscribe' == action)
		{
			room = param;
			if (isSubscribed)
			{ 
				if (ws) ws.sendSubscribe();
				else return false;
			}
			return true; 
		}
		if (!isConnected) return false;
		if (!this.roomIsSet()) return false;

		var data = [2, hash.newId(), this.getRoomURL(), action];
		if (typeof param != 'undefined' && param !== null) data.push(param);
console.log("sending", data);
		ws.send(JSON.stringify(data));
		return true;
	};

	interface_public.getEvents = function()
	{
//if (events.length) console.log('getEvents', events.length, events);
		var eventsCopy = events;
		events = [];
		timestamp = now();
		return eventsCopy;
	};

	interface_public.addEvent = function(e) { events.push(e); };
	interface_public.expired = function() { return now() - timestamp > 30000; };
	interface_public.getRoomURL = function() { return "http://localhost/game/" + room; };
	interface_public.roomIsSet = function() { return !!room; };
	interface_public.disconnect = function() { if (ws) ws.close(); };
	interface_public.setConnected = function() { isConnected = true; };

	return interface_public;
}

var hash = {};
hash.idchars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
hash.idlen = 16;

hash.newId = function()
{
   var id = "";
   for (var i = 0; i < hash.idlen; i += 1) {
      id += hash.idchars.charAt(Math.floor(Math.random() * hash.idchars.length));
   }
   return id;
};


