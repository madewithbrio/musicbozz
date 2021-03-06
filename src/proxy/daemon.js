var http = require('http');

require('./WebClient');

var instanceList = {};

var proxy = http.createServer(

	function(request, response)
	{
		var url = request.url,
			actionMatch = url.match(/([^\/?]+)/),
			events;
		if (!actionMatch) return http_error(response, 500, 'no action');
		
		var action = actionMatch[1];
		
		var idMatch = url.match(/[^\/?]+\/([^\/?]+)/);
		if (!idMatch) return http_error(response, 500, 'no id');
		
		var id = idMatch[1];

		var paramMatch = url.match(/[^\/?]+\/[^\/?]+\/([^\/?]+)/);
		param = paramMatch ? paramMatch[1]: null;

		switch(action)
		{
			case 'subscribe': 
				if (!param) return http_error(response, 500, 'parameter required');
				if (instanceList[id]) delete instanceList[id];
				instanceList[id] = new WebClient(id);

			case 'setPlayerName':
			case 'setAnswer': 
				if (!param) return http_error(response, 500, 'parameter required');
				if (action == 'setAnswer') 
				{
console.log('<<<< setAnswer', param);
					param = parseInt(param, 10);
console.log('>>>> setAnswer after', param);
					if(!param) param = 0;
console.log('=>=> setAnswer end', param);
				}
		
			case 'listPlayers':
			case 'newQuestion':
			case 'timeEnded':
			case 'setReadyToPlay':
				if (!instanceList[id]) return http_error(response, 500, 'not ready yet! have you subscribed?');
				var res = instanceList[id].send(action, param);
				if (!res) return http_error(response, 500, 'not ready yet! have you subscribed?');
				break;

			case 'pull':
				if (!instanceList[id]) return http_error(response, 500, 'invalid id');
				break;

			default: return http_error(response, 500, 'invalid action');
		}
//if (action != 'pull') console.log(id, action, param);

		if (url.match(/\?xml$/))
		{
			contentType = 'text/xml';
			if (action == 'pull') events = to_xml(instanceList[id].getEvents());
			else events = to_xml([]);
		}
		else
		{
			contentType = 'application/json‎';
			if (action == 'pull') events = JSON.stringify(instanceList[id].getEvents());
			else events = '[]';
		}
//if (action != 'pull') console.log(events);
		response.writeHead(200, {'Content-Type': contentType});
//console.log("====\n" + events + "\n====");
		response.end(events);
	}
);

function http_error(response, code, text)
{
//console.log("---- Error returned: " + text);
    response.writeHead(code, {'Content-Type': 'text/plain'});
    response.end(text);
};

function clearClients()
{
	for(var id in instanceList)
		if (instanceList[id].expired())
		{
//console.log(id, "expired");
			instanceList[id].disconnect();
			delete instanceList[id];
//console.log("instanceList", instanceList);
		}
			
	setTimeout(clearClients, 1000);
};

proxy.listen(9999, '127.0.0.1');

clearClients();

function to_xml(data)
{
	var l = data.length;

//console.log(data);
	var xml = '<?xml version="1.0" encoding="utf-8"?>\n<Events><TotalEvents>' + l + '</TotalEvents>';
	for(var i = 0; i < l; i++)
	{
		var ev = data[i];
		xml += "<Event>";
		if (!ev.action) ev.action = 'ignore';
		xml += to_xml_val(ev);
//console.log(ev);
		xml += "</Event>";
	} 
	return xml += "</Events>";
};

function to_xml_val(data)
{
	var xml = '';
//console.log("   to_xml_val", data);
	if ('object' == typeof(data))
	{
		for (var key in data)
			if (data.hasOwnProperty(key))
			{
				if (isNaN(key))
					xml+= "<" + key + ">" + to_xml_val(data[key]) + "</" + key + ">";
				else
					xml+= "<ListItem>" + to_xml_val(data[key]) + "</ListItem>";
			}
		return xml;
	}
	else
		return '<![CDATA[' + data + ']]>';
}
