
var WebSocket = require('faye-websocket');

var now = function() {return (new Date()).getTime(); };

WebClient = function() {

	var interface_public = {}, 
		//ws = new WebSocket.Client('ws://localhost:9000/');
		ws = new WebSocket.Client('ws://62.28.238.103:9000/'),
		timestamp = now(),
		events = [],
		room;

	var getRoomURL = function() { return "http://localhost/game/" + this.room; };

	ws.onopen = function(event) {
	  console.log('open');
//	  ws.send('Hello, world!');
	};

	ws.onmessage = function(event) {
		console.log('message', event.data);
		var data = JSON.parse(event.data);
		var typeId = data[0];
		if (typeId == 0) 
			ws.send(JSON.serialize([5, this.getRoomURL()]));
		else if (typeId == 3 || typeId == 4 || typeId == 8) 
			this.events.push(data[2]);
	};

	ws.onclose = function(event) {
	  console.log('close', event.code, event.reason);
	  ws = null;
	};

	interface_public.send = function(action, param)
	{
		if ('subscribe' == action) return this.room = param;
		var data = [2, hash.newId(), this.getRoomURL(), action];
		if (param) data.push(param);
console.log("sending", data);
		ws.send(JSON.stringify(data));
	};

	interface_public.getEvents = function()
	{
		var events = this.events;
		this.events = [];
		this.timestamp = now();
		return events;
	};

	interface_public.expired = function()
	{
		return now() - this.timestamp > 120000;
	};

	return interface_public;
}

var hash = {};
hash.idchars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
hash.idlen = 16;

hash.newId = function()
{
   var id = "";
   for (var i = 0; i < ab.idlen; i += 1) {
      id += ab.idchars.charAt(Math.floor(Math.random() * ab.idchars.length));
   }
   return id;
};


