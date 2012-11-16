var http = require('http');
var WebSocket = require('faye-websocket');



var proxy = http.createServer(function(request, response){

    response.writeHead(200, {'Content-Type': 'text/plain'});
    response.end('okay');
});

proxy.listen(9001);

var instance = function() {

	var interface_public = {}, 
		ws  = new WebSocket.Client('ws://localhost:9000/');
	ws.onopen = function(event) {
	  console.log('open');
	  ws.send('Hello, world!');
	};
	ws.onmessage = function(event) {
	  console.log('message', event.data);
	};
	ws.onclose = function(event) {
	  console.log('close', event.code, event.reason);
	  ws = null;
	};

	return interface_public;
}

