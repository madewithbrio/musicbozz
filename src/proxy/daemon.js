var http = require('http');

require('./WebClient');

var instanceList = {};

var proxy = http.createServer(

	function(request, response)
	{
		var url = request.url,
			actionMatch = url.match(/([^\/]+)/);
		if (!actionMatch) return http_error(response, 500, 'no action');
		
		var action = actionMatch[1];
		
		var idMatch = url.match(/[^\/]+\/([^\/]+)/);
		if (!idMatch) return http_error(response, 500, 'no id');
		
		var id = idMatch[1];

		var paramMatch = url.match(/[^\/]+\/[^\/]+\/([^\/]+)/);
		param = paramMatch ? paramMatch[1]: null;

		switch(action)
		{
			case 'setPlayerName':
			case 'setAnswer': 
			case 'subscribe': 
				if (!param) return http_error(response, 500, 'parameter required');

			case 'listPlayers':
			case 'newQuestion':
			case 'timeEnded':
			case 'setAnswer':
			case 'setReadyToPlay':
			case 'pull':
				if (!instanceList[id]) instanceList[id] = new WebClient(id);
				instanceList[id].send(action, param); 
				break;

			default: return http_error(response, 500, 'invalid action');
		}
console.log(id, action, param);
		response.writeHead(200, {'Content-Type': 'text/plain'});
		
		var events = JSON.stringify(instanceList[id].getEvents());
console.log(events);
		response.end(events);
	}
);

function http_error(response, code, text)
{
    response.writeHead(code, {'Content-Type': 'text/plain'});
    response.end(text);
};

function clearClients()
{
	for(var id in instanceList)
		if (instanceList[id].expired())
		{
console.log(id, "expired");
			instanceList[id].disconnect();
			delete instanceList[id];
console.log("instanceList", instanceList);
		}
			
	setTimeout(clearClients, 10000);
};

proxy.listen(9001);

clearClients();

