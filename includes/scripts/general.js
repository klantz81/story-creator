String.prototype.possessive = function() {
        return /s$/.test(this) ? (this+"'") : (this+"'s");
}

function stop(e) {
	e = e ? e : window.event;
	if (typeof(e.preventDefault) == 'function') e.preventDefault();
	if (typeof(e.stopPropagation) == 'function') e.stopPropagation();
	e.returnValue = false;
	e.cancelBubble = true;
	return e;
};

function clone(obj) {
        var copy;

        if (null == obj || "object" != typeof obj)
                return obj;

        if (obj instanceof Date) {
                copy = new Date();
                copy.setTime(obj.getTime());
                return copy;
        }

        if (obj instanceof Array) {
                copy = [];
                for (var i = 0; i < obj.length; i++)
                        copy[i] = clone(obj[i]);
                return copy;
        }

        if (obj instanceof Object) {
                copy = {};
                for (var attr in obj) {
                        if (obj.hasOwnProperty(attr))
                                copy[attr] = clone(obj[attr]);
                }
                return copy;
        }

        throw new Error("Unable to copy obj! Its type isn't supported.");
}

var request = {
	get: function(url, params, callback) {
                if (url.length < 1) url = window.location.href;
                
		var list = Array();
		for (var i in params)
			list.push(encodeURIComponent(i) + "=" + encodeURIComponent(params[i]));

		var xhr = new XMLHttpRequest();
		xhr.open('GET', url + (/\?/.test(url) ? '&' : '?') + list.join("&"), true);
		xhr.onreadystatechange = function(evt) {
			if (xhr.readyState == 4 && xhr.status == 200) {
				if (typeof callback == 'function') callback(xhr.responseText);
				xhr.onreadystatechange = null;
				xhr = null;
			}
		};
		xhr.send(null);
	},
	post: function(url, params, callback) {
                if (url.length < 1) url = window.location.href;
                
		var list = Array();
		for (var i in params)
			list.push(encodeURIComponent(i) + "=" + encodeURIComponent(params[i]));

		var xhr = new XMLHttpRequest();
		xhr.open('POST', url, true);
		xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		//xhr.setRequestHeader("Content-length", list.length);
		//xhr.setRequestHeader("Connection", "close");
		xhr.onreadystatechange = function(evt) {
			if (xhr.readyState == 4 && xhr.status == 200) {
				if (typeof callback == 'function') callback(xhr.responseText);
				xhr.onreadystatechange = null;
				xhr = null;
			}
		};
		xhr.send(list.join("&"));
	}
};

var dimensions = {
	setScroll: function(x, y, el) {
                if (typeof(el) != 'undefined') {
                        el.scrollTop = y;
                        el.scrollLeft = x;
                } else
                        window.scrollTo(x, y);
        },
	getScroll: function(el) {
                if (el)
                        return { left:(el.scrollX && el.scrollX > 0 ? el.scrollX : (el.scrollLeft && el.scrollLeft > 0 ? el.scrollLeft : 0)),
                                top: (el.scrollY && el.scrollY > 0 ? el.scrollY : (el.scrollTop  && el.scrollTop  > 0 ? el.scrollTop  : 0)),
                                width: (el.scrollWidth && el.scrollWidth > 0 ? el.scrollWidth : (el.scrollWidth  && el.scrollWidth  > 0 ? el.scrollWidth  : 0)),
                                height: (el.scrollHeight && el.scrollHeight > 0 ? el.scrollHeight : (el.scrollHeight  && el.scrollHeight  > 0 ? el.scrollHeight  : 0)),
                        };
                else
                        return { left:(window.scrollX && window.scrollX > 0 ? window.scrollX : (window.document.body.scrollLeft && window.document.body.scrollLeft > 0 ? window.document.body.scrollLeft : (window.document.body.parentNode.scrollLeft && window.document.body.parentNode.scrollLeft > 0 ? window.document.body.parentNode.scrollLeft : 0))),
                                top: (window.scrollY && window.scrollY > 0 ? window.scrollY : (window.document.body.scrollTop  && window.document.body.scrollTop  > 0 ? window.document.body.scrollTop  : (window.document.body.parentNode.scrollTop  && window.document.body.parentNode.scrollTop  > 0 ? window.document.body.parentNode.scrollTop  : 0))) };
	},
	getWindow: function() {
		return { width: (window.innerWidth  ? window.innerWidth  : (document.body.clientWidth  ? document.body.clientWidth  : document.documentElement.clientWidth )),
			 height:(window.innerHeight ? window.innerHeight : (document.body.clientHeight ? document.body.clientHeight : document.documentElement.clientHeight)) };
	},
	getOffsets: function(el) {
		var _x = 0; var width  = el.offsetWidth;
		var _y = 0; var height = el.offsetHeight;
		while(el && !isNaN(el.offsetLeft) && !isNaN(el.offsetTop)) {
			_x += el.offsetLeft;
			_y += el.offsetTop;
			if (typeof(el.scrollLeft) != 'undefined' && el != document.body) _x -= parseInt(el.scrollLeft);
			if (typeof(el.scrollTop)  != 'undefined' && el != document.body) _y -= parseInt(el.scrollTop);
			el = el.offsetParent;
		}
		return {left:_x, top:_y, width:width, height:height};
	},
	getOffsetsWithScroll: function(el) {
		var _x = 0; var width  = el.offsetWidth;
		var _y = 0; var height = el.offsetHeight;
		while(el && !isNaN(el.offsetLeft) && !isNaN(el.offsetTop)) {
			_x += el.offsetLeft;
			_y += el.offsetTop;
			el = el.offsetParent;
		}
		return {left:_x, top:_y, width:width, height:height};
	},
	getMouse: function(event) {
		var evt = event ? event : window.event;
		var sc = dimensions.getScroll();
		return { left:(evt.pageX ? evt.pageX : (evt.clientX + sc.left)), sleft: sc.left,
			 top: (evt.pageY ? evt.pageY : (evt.clientY + sc.top)),  stop:  sc.top  };
	},
	isContained: function(x, y, dims) {
                return x >= dims.left && x < dims.left + dims.width && y >= dims.top && y < dims.top + dims.height;
        }
};

var svg = {
        circle: function(svg, x, y) {
                var circle = document.createElementNS('http://www.w3.org/2000/svg','circle');
                circle.setAttribute('cx', x);
                circle.setAttribute('cy', y);
                circle.setAttribute('r', 6);
                circle.setAttribute('stroke', '#fff');
                circle.setAttribute('stroke-width', '2');
                circle.setAttribute('fill', '#579');
                svg.appendChild(circle);
        },
        line: function(svg, x1, y1, x2, y2, l) {
                var dx = x2 - x1;
                var dy = y2 - y1;
                
                var t = 1000000;
                
                if (Math.abs(dx) > 0) {
                        var t0 = ((x2 - editor.card_width / 2) - x1) / dx;
                        var ny0 = y1 + dy * t0;
                        if (ny0 >= y2 - editor.card_height / 2 && ny0 <= y2 + editor.card_height / 2 && t0 < t)
                                t = t0;
                                
                        var t1 = ((x2 + editor.card_width / 2) - x1) / dx;
                        var ny1 = y1 + dy * t1;
                        if (ny1 >= y2 - editor.card_height / 2 && ny1 <= y2 + editor.card_height / 2 && t1 < t)
                                t = t1;
                }
                if (Math.abs(dy) > 0) {
                        var t0 = ((y2 - editor.card_height / 2) - y1) / dy;
                        var nx0 = x1 + dx * t0;
                        if (nx0 >= x2 - editor.card_width / 2 && nx0 <= x2 + editor.card_width / 2 && t0 < t)
                                t = t0;
                                
                        var t1 = ((y2 + editor.card_height / 2) - y1) / dy;
                        var nx1 = x1 + dx * t1;
                        if (nx1 >= x2 - editor.card_width / 2 && nx1 <= x2 + editor.card_width / 2 && t1 < t)
                                t = t1;
                }

                if (t < 1000000) {
                        x2 = x1 + dx * t;
                        y2 = y1 + dy * t;
                }
                
                var line = document.createElementNS('http://www.w3.org/2000/svg','line');
                if (typeof(l) != 'undefined')
                        line = l;
                        
                line.setAttribute('x1', x1);
                line.setAttribute('y1', y1);
                line.setAttribute('x2', x2);
                line.setAttribute('y2', y2);
                line.setAttribute('stroke', '#fff');
                line.setAttribute('stroke-width', '2');
                line.setAttribute('marker-end', 'url(#triangle)');
                
                if (typeof(l) == 'undefined')
                        svg.appendChild(line);
                
                return line;
        }
}

function animate(obj, parameter, unit, from, to, time, handler) {
        function _animate(item) {
                var duration = item.time,
                end      = new Date().getTime() + duration;
                var active = true;
                
                var step = function() {
                        var current   = new Date().getTime(),
                        remaining = end - current;

                        if(remaining < 10 || !active) {
                                item.run(1);
                                if (typeof(item.handler) == 'function')
                                        item.handler();
                                return;
                        } else item.run(1 - remaining/duration);
//                        setTimeout(step, 5);
                        window.requestAnimationFrame(step);
                };
                
                step();
                return {
                        kill: function() {
                                active = false;
                        }
                };
        }
        
	return _animate({time:time,handler:handler,run:function(rate) {
		obj[parameter] = (from + rate * (to - from)) + unit;
	}});
}
