var ATU = {
	tid: 0,
	time: 0,
	showspinner: true,
	spinner: false,
	init: function(tid, time, spinner)
	{
		if(!tid)
			return;
		ATU.tid = tid;
		ATU.showspinner = spinner;
		ATU.time = time;
		if(ATU.time > 0)
		{
			setInterval(function()
			{
				ATU.update();
			}, time * 1000);
		}
	},
	
	update: function()
	{
		var elements = $$('.post_body');
		var numposts = elements.length;
		var lastpid = elements[elements.length-1].id;
		lastpid = lastpid.replace('pid_', '');
		ATU.spinnershow();

		new Ajax.Request('ajaxthreadupdate.php?pid=' + ATU.tid + '&lastid=' + lastpid + '&numposts=' + numposts, {
			method: 'GET', postBody: null, onComplete: function(request) {
				if(request.status == 200) {
					$$('.pagination').each(function(element){
						element.hide();
					});
					$('posts').innerHTML += request.responseText;
					var scripts = request.responseText.extractScripts();
					scripts.each(function(script)
					{
						eval(script);
					});
				}
				ATU.spinnerhide();
			}
		});
	},

	spinnershow: function()
	{
		ATU.spinnerhide();
		if(ATU.showspinner)
		{
			ATU.spinner = new ActivityIndicator("body", {
				image: imagepath + "/spinner_big.gif"
			});
		}
	},

	spinnerhide: function()
	{
		if(ATU.spinner)
		{
			ATU.spinner.destroy();
			ATU.spinner = '';
		}
	}
};