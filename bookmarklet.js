(function() {
	var shadowSize   = 8;
    var canvasWidth  = 350;
    var canvasHeight = 150;

    var Geometry     = {};

    // Geometry.js came from: http://www.davidflanagan.com/javascript5/
    if (window.innerWidth) { 
        Geometry.getViewportWidth    = function() { return window.innerWidth; };
        Geometry.getVerticalScroll   = function() { return window.pageYOffset; }; //or : window.pageYOffset || document.body.scrollTop || document.documentElement.scrollTop;
    } else if (document.documentElement && document.documentElement.clientWidth) {
        Geometry.getViewportWidth    = function() { return document.documentElement.clientWidth; };
        Geometry.getVerticalScroll   = function() { return document.documentElement.scrollTop; };
    } else if (document.body.clientWidth) {
        Geometry.getViewportWidth    = function() { return document.body.clientWidth; };
        Geometry.getVerticalScroll   = function() { return document.body.scrollTop; };
    }

	function bookmarklet() {
		if (document.getElementById("ipi_container")) {
			return;
		}
		
		var container = div();
		
		container.id 			 = "ipi_container";
		container.style.position = "absolute";
        container.style.top 	 = Geometry.getVerticalScroll() + "px";
        container.style.right	 = (Geometry.getViewportWidth() - canvasWidth)/2 + "px";
        container.style.width	 = canvasWidth + shadowSize + "px";
        container.style.height   = canvasHeight + shadowSize + "px";
		container.style.zIndex   = 100000;
				
		var shadow = div(container);
		
		shadow.id 				 	 = "ipi_shadow";
		shadow.style.backgroundColor = "black";
		shadow.style.position		 = "absolute";
		shadow.style.zIndex 		 = 0;
		shadow.style.top 			 = "0";
		shadow.style.right 		     = "0";
		
		setOpacity(shadow, 0.3);
		
		var foreground = div(container);
		
		foreground.id 					 = "ipi_foreground";
		foreground.style.backgroundColor = "white";
		foreground.style.zIndex 		 = 2;
		foreground.style.position 	     = "absolute";
		foreground.style.width 			 = canvasWidth + "px";
		foreground.style.height 		 = canvasHeight + "px";		
		foreground.style.top 		 	 = 0;
		foreground.style.right	 	     = shadowSize + "px";
		
		var headerStyle  = "z-index:3;height:30px;margin:0;padding:0;background-color:#DDD;border-bottom:1px solid #CCC;cursor:default !important;";
		var captionStyle = "display:block;font:bold 13px 'Lucida Grande', Arial, sans-serif;text-shadow:#FFF 0 1px 0;padding:.5em 2em .5em .75em;margin:0;text-align:left;color:#000";		
		var closeStyle   = "display:block;position:absolute;right:5px;top:4px;padding:2px 3px;font-weight:bold;text-decoration:none;font-size:13px;color:#777;border:none;";
		var contents 	 = '<div id="ipi_header" style="' + headerStyle + '"><div id="ipi_caption" style="' + captionStyle + '">Install a Plugin</div><a href="#" title="Close window" id="ipi_close" style="' + closeStyle + '">x</a></div><iframe frameborder="0" id="ipi_iframe" style="background:white;width:100%;height:' + (canvasHeight - 30) + 'px;border:0px;padding:0px;margin:0px"></iframe>';
				
		foreground.innerHTML = contents;
				
		document.body.appendChild(container);
		
		document.getElementById('ipi_close').onclick = closeFrame;	
		
		setFrameUrl();
		
		var lastShadowWidth  = 0;
		var lastShadowHeight = 0;

		function resizeShadow() {
			var shadow	   = document.getElementById("ipi_shadow");
			var foreground = document.getElementById("ipi_foreground");
			
			if (!shadow || !foreground) {
				clearInterval(interval);
				return;
			}	
			
			if (lastShadowWidth != foreground.offsetWidth || lastShadowHeight != foreground.offsetHeight) {
				lastShadowWidth     = foreground.offsetWidth;
				lastShadowHeight    = foreground.offsetHeight;		
				shadow.style.width  = (lastShadowWidth + shadowSize*2) + "px";
				shadow.style.height = (lastShadowHeight + shadowSize) + "px";
			}
		}
		
		var interval = window.setInterval(function() {
			resizeShadow();
		}, 50);
		
		resizeShadow();
		
		window.onscroll = function() {
			container.style.top = Geometry.getVerticalScroll() + "px";
		};	
	}
	
	function div(opt_parent) {
		var e = document.createElement("div");
		
		e.style.padding  = "0";
		e.style.margin   = "0";
		e.style.border   = "0";
		e.style.position = "relative";
		
		if (opt_parent) {
			opt_parent.appendChild(e);
		}
		
		return e;
	}
	
	function setOpacity(element, opacity) {
		if (navigator.userAgent.indexOf("MSIE") != -1) {
			element.style.filter = "alpha(opacity=" + (Math.round(opacity*100)) + ")";
		} else {
			element.style.opacity = opacity;
		}
	}
	
	function setFrameUrl() {
		var iframe;
		
		if (navigator.userAgent.indexOf("Safari") != -1) {
			iframe = frames["ipi_iframe"];
		} else {
			iframe = document.getElementById("ipi_iframe").contentWindow;
		}
		if (!iframe) {
			return;
		}
		
		var wordpressAdmin = bookmarkletCode = bookmarkletJS = '';

		bookmarkletJS    = document.getElementById('ipi_javascript').src;
		wordpressAdmin	 = bookmarkletJS.split('/');
		wordpressAdmin 	 = wordpressAdmin.slice(0, wordpressAdmin.length-4).join('/') + '/wp-admin/';
		bookmarkletCode  = bookmarkletJS.match(/\?.*ipi_bookmarklet=([0-9]+)/)[1];
		 		
		var downloadURL = '';
		
		if (/downloads\.wordpress\.org/i.test(location.href)) {
			downloadURL = location.href;
		} else if (/wordpress\.org\/extend\/plugins/i.test(location.href)) {
			downloadURL = location.href;
		} else {
			var links = document.getElementsByTagName('a');
			
			var directLinks    = new Array();
			var indirectLinks  = new Array();
			var possibleLinks  = new Array();
			var possibleTitles = new Array();
			
			var matches		   = new Array();
			
			for (var i=0; i<links.length; i++) {
				var link = links[i];
				
				if (/downloads\.wordpress\.org\/plugin\/.*\.zip/i.test(link.href)) {
					if (!inArray(link.href, directLinks)) {
						directLinks.push(link.href);
					}
				} else if (matches = link.href.match(/wordpress\.org\/extend\/plugins\/.*\//i)) {
					if (matches.length == 1 && !inArray('http://' + matches[0], indirectLinks)) {
						indirectLinks.push('http://' + matches[0]);
					}
				} else if (/\.zip/i.test(link.href)) {
					if (!inArray(link.href, possibleLinks)) {
						possibleLinks.push(link.href);
					}
				} else if (/download/i.test(link.title)) {
					if (!inArray(link.href, possibleTitles)) {
						possibleTitles.push(link.href);
					}
					
					if (link.getAttribute('onclick')) {
						matches = link.getAttribute('onclick').toString().match(/downloads\.wordpress\.org\/plugin\/.*\.zip/i);
						if (matches.length == 1 && !inArray('http://' + matches[0], directLinks)) {
							directLinks.push('http://' + matches[0]);
						}
					}
				} 						
			}
			
			if (directLinks.length > 1) {
				var found = 0;
				for (var i=0; i<directLinks.length; i++) {
					if (!(/\.[0-9\.]+\.zip/i.test(directLinks[i]))) {
						downloadURL = directLinks[i];
						found++;
					}
				}
				if (found > 1) {
					downloadURL = '';
				}
			}
			
			if (!downloadURL) {
				if (directLinks.length == 1) {
					downloadURL = directLinks[0];
				} else if (indirectLinks.length == 1) {
					downloadURL = indirectLinks[0];
				} else if (possibleLinks.length == 1) {
					downloadURL = possibleLinks[0];					
				} else if (possibleTitles.length == 1) {
					downloadURL = possibleTitles[0];
				}
			} 
		}
		
		if (!downloadURL) {
			downloadURL = 'notfound';
		}
		
		var url = wordpressAdmin + 'plugin-install.php?ipi_bookmarklet=' + bookmarkletCode + '&url=' + encodeURIComponent(downloadURL);
		
		try {
			iframe.location.replace(url);
		} catch (e) {
			iframe.location = url; // safari
		}
	}
	
	function closeFrame() {
		window.onscroll = null; 
		
		var container  = document.getElementById('ipi_container');
		var shadow     = document.getElementById('ipi_shadow');
		var foreground = document.getElementById('ipi_foreground');
		
		foreground.parentNode.removeChild(foreground);
		shadow.parentNode.removeChild(shadow);
		container.parentNode.removeChild(container);	
		
		return false;	
	}
	
	function inArray(what, where) {
		for (var i=0; i<where.length; i++) {
			if (what == where[i]) {
				return true;
			}
		}
		
		return false;
	}
	
	bookmarklet();
})();