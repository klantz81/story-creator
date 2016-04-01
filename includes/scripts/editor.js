var editor = {
        zindex:100,
        
        card_width:150,
        card_height:150,
        
        max_w: 0,
        max_h: 0,
        buffer_w: 2000,
        buffer_h: 2000,
        
        id: null,
        disabled_scroll: false,
        
        init: function() {
                var dv = document.getElementById('create-story');
                dv.onmousedown = stop;
                dv.onclick = function(e) {
                        stop(e);
                        if (s.story_id)
                                editor.save(editor.create)
                        else
                                editor.create();
                }
                
                var dv = document.getElementById('load-story');
                dv.onmousedown = stop;
                dv.onclick = function(e) {
                        stop(e);
                        
                        if (s.story_id)
                                editor.save(editor.list);
                        else
                                editor.list();
                }
                
                var dv = document.getElementById('save-story');
                dv.onmousedown = stop;
                dv.onclick = function(e) {
                        stop(e);
                        
                        if (s.story_id)
                                editor.save();
                        else
                                bootbox.alert("A document has not yet been loaded.");
                }
                
                var dv = document.getElementById('render-story');
                dv.onmousedown = stop;
                dv.onclick = function(e) {
                        stop(e);
                
                        if (s.story_id)
                                editor.save(function() {
                                        editor.disabled_scroll = true;
                                        
                                        var re = document.getElementById('render'); re.innerHTML = ''; re.style.display = 'block';
                                        var iframe = document.createElement('iframe'); re.appendChild(iframe);
                                        
                                        iframe.src = 'story/editor/'+s.story_id;

                                        re.onmousedown = stop;
                                        re.onclick = function(e) {
                                                stop(e);
                                                editor.disabled_scroll = false;
                                                re.style.display = 'none';
                                        };
                                });
                        else
                                bootbox.alert("A document has not yet been loaded.");
                }
                
                var input = document.getElementById('publish-toggle');
                input.onchange = function(e) {
                        if (s.story_id)
                                request.post('', {a:'publish', id:s.story_id, value:this.checked>0?1:0}, function(r) {
                                });
                };
                
                var dv = document.getElementById('rename-story');
                dv.onmousedown = stop;
                dv.onclick = function(e) {
                        stop(e);
                        
                        if (s.story_id)
                                editor.rename();
                        else
                                bootbox.alert("A document has not yet been loaded.");
                }
                
                var dv = document.getElementById('delete-story');
                dv.onmousedown = stop;
                dv.onclick = function(e) {
                        stop(e);
                        
                        if (s.story_id)
                                editor.remove();
                        else
                                bootbox.alert("A document has not yet been loaded.");
                }
                
                var dv = document.getElementById('logout');
                dv.onmousedown = stop;
                dv.onclick = function(e) {
                        stop(e);
                        if (s.story_id)
                                editor.save(function() {
                                        window.location.replace('logout');
                                });
                        else
                                window.location.replace('logout');
                }
                
                editor.load(0, editor.edit);
                
        },
        render: function(card) {
                if (typeof(card.lines) != 'undefined')
                        card.lines = [];
                        
                var el = document.getElementById('edit-content');
                
                var div = document.createElement('div'); div.className = 'card'; div.style.width = editor.card_width + 'px'; div.style.height = editor.card_height + 'px'; div.style.zIndex = editor.zindex++;
                var dv0 = document.createElement('div'); dv0.innerHTML = card.id.replace(/</g, '&#60;').replace(/>/g, '&#62;').replace(/\n/g, '<br>'); div.appendChild(dv0);
                var dv1 = document.createElement('div'); dv1.innerHTML =  card.text.replace(/</g, '&#60;').replace(/>/g, '&#62;').replace(/\n/g, '<br>'); div.appendChild(dv1);
                
                card.div = div;
                card.id_div = dv0;
                card.text_div = dv1;

                var sc = dimensions.getScroll();
                
                if (typeof(card.left) == 'undefined') card.left = sc.left + 128;
                if (typeof(card.top) == 'undefined') card.top = sc.top + 128;
                        
                editor.max_w = Math.max(editor.max_w, card.left + editor.card_width);
                editor.max_h = Math.max(editor.max_h, card.top + editor.card_height);
                
                div.style.left = card.left + "px";
                div.style.top = card.top + "px";
                
                el.appendChild(div);

                function buildLinks() {
                        card.links = [];
                        
/*                        var tree = markdown.parse(card.text);
                        var links = [];
                        var texts = [];

                        (function findLinks(obj) {
                        if (obj[0] === "link" ) {
                                links.push(obj[1].href);
                                texts.push(obj[2]);
                        }
                        for (var i = 0; i < obj.length; i++) {
                                if (obj[i] instanceof Array) {
                                        findLinks(obj[i]);
                                }
                        }
                        })(tree);

                        for (var j = 0; j < links.length; j++)
                                card.links.push({text: texts[j], id: links[j]});
*/
                        var temp = document.createElement('div');
                        temp.innerHTML = marked(card.text);
                        var links = temp.getElementsByTagName('a');
                        for (var j = 0; j < links.length; j++)
                                card.links.push({text: links[j].textContent, id: links[j].getAttribute('href')});
                }
                
                buildLinks();
                
                function handleLinks() {
                        for (var j = 0; j < card.out_links.length; j++) {
                                svg.line(document.getElementById('svg'),
                                           card.left + editor.card_width / 2,
                                           card.top + editor.card_height / 2, 
                                           card.out_links[j].j.left + editor.card_width / 2,
                                           card.out_links[j].j.top + editor.card_height / 2,
                                           card.out_links[j].l);
                        }
                        for (var j = 0; j < card.in_links.length; j++) {
                                svg.line(document.getElementById('svg'),
                                           card.in_links[j].j.left + editor.card_width / 2,
                                           card.in_links[j].j.top + editor.card_height / 2,
                                           card.left + editor.card_width / 2,
                                           card.top + editor.card_height / 2, 
                                           card.in_links[j].l);
                        }
                }
                
                div.onmousedown = function(e) {
                        stop(e);
                        
                        div.style.zIndex = editor.zindex++;
                        
                        var els = document.getElementsByClassName('card');
                        for (var i = 0; i < els.length; i++)
                                els[i].className = 'card';
                        div.className += ' selected';
                        
                        var mo = dimensions.getMouse(e);
                        
                        document.onmousemove = function(e) {
                                var m = dimensions.getMouse(e);
                                
                                card.left = card.left + m.left - mo.left;
                                card.top = card.top + m.top - mo.top;
                                
                                mo = m;
                                
                                if (card.left < 0)
                                        card.left = 0;
                                if (card.top < 0)
                                        card.top = 0;
                                
                                div.style.left = card.left + 'px'
                                div.style.top = card.top + 'px'
                                
                                editor.max_w = Math.max(editor.max_w, card.left + editor.card_width);
                                editor.max_h = Math.max(editor.max_h, card.top + editor.card_height);
                                
                                var ed = document.getElementById('edit');
                                
                                ed.style.width = (editor.max_w + editor.buffer_w) + 'px';
                                ed.style.height = (editor.max_h + editor.buffer_h) + 'px';
                                
                                handleLinks();
                        };
                        
                        document.onmouseup = function(e) {
                                document.onmousemove = null;
                                document.onmouseup = null;
                                
                        };
                };
                
                div.ondblclick = function(e) {
                        stop(e);
                        editor.disabled_scroll = true;

                        document.getElementById('edit-card').style.display = 'block';
                        
                        document.getElementById('edit-card-id').value = card.id;
                        
                        var code_editor = ace.edit("edit-card-code");
                        code_editor.setTheme("ace/theme/chrome");
                        var JavascriptMode = ace.require("ace/mode/javascript").Mode;
                        code_editor.session.setMode(new JavascriptMode());
                        
                        code_editor.setFontSize(14);
                        code_editor.setValue(card.code?card.code:'', 1);
                        
                        
                        var text_editor = ace.edit("edit-card-text");
                        text_editor.setTheme("ace/theme/chrome");
                        var MarkdownMode = ace.require("ace/mode/markdown").Mode;
                        text_editor.session.setMode(new MarkdownMode());
                        
                        text_editor.setFontSize(14);
                        text_editor.setValue(card.text?card.text:'', 1);
                        text_editor.focus();
                        
                        
                        
                        var sv = document.getElementById('edit-card-save');
                        sv.onmousedown = stop(e);
                        sv.onclick = function(e) {
                                stop(e);
                                card.code = code_editor.getValue();
                                card.text = text_editor.getValue();

                                var temp = document.getElementById('edit-card-id').value;
                                
                                var hit = false;
                                for (var i = 0; i < s.cards.length; i++)
                                        if (s.cards[i].id == temp && s.cards[i] != card)
                                                hit = true;
                                        
                                if (hit) {
                                        bootbox.alert("Could not update the ID.  Another node exists with the specified ID.");
                                } else {
                                        if (card.id != temp) {
                                                
                                                for (var j = 0; j < s.cards.length; j++) {
                                                        var matches = null;
                                                        var pattern = /\[(.*?)\]\((.*?)(\)|\s\")/g;
                                                        while (matches = pattern.exec(s.cards[j].text)) {
                                                                if (matches[2] == card.id) {
                                                                        s.cards[j].text = s.cards[j].text.replace(matches[0], "["+matches[1]+"]("+temp+matches[3]);
                                                                }
                                                        }

                                                }
                                                
                                                card.id = temp;
                                        }
                                }
                                
                                if (/selected/.test(sc.className))
                                        s.start_id = card.id;
                                
                                dv0.innerHTML = card.id.replace(/</g, '&#60;').replace(/>/g, '&#62;').replace(/\n/g, '<br>');
                                dv1.innerHTML = card.text.replace(/</g, '&#60;').replace(/>/g, '&#62;').replace(/\n/g, '<br>');
                                
                                editor.edit();
                                
                                code_editor.destroy();
                                text_editor.destroy();
                                
                                editor.disabled_scroll = false;
                                document.getElementById('edit-card').style.display = 'none';
                                
                                editor.save();
                        };
                        
                        var rm = document.getElementById('edit-card-remove');
                        rm.onmousedown = stop(e);
                        rm.onclick = function(e) {
                                stop(e);

                                if (s.start_id == card.id) {
                                        bootbox.alert("You cannot remove the start node.");
                                        return;
                                }

                                bootbox.confirm("Remove this node?", function(res) {
                                        
                                        if (res) {
                                                for (var k = 0; k < s.cards.length; k++) {
                                                        if (s.cards[k].id == card.id) {
                                                                s.cards.splice(k, 1);
                                                                div.parentNode.removeChild(div);
                                                                break;
                                                        }
                                                }

                                                editor.edit();
                                                
                                                code_editor.destroy();
                                                text_editor.destroy();
                                                editor.disabled_scroll = false;
                                                document.getElementById('edit-card').style.display = 'none';
                                                
//                                                editor.save();
                                        }
                                });
                        };
                        
                        var ca = document.getElementById('edit-card-cancel');
                        ca.onmousedown = stop(e);
                        ca.onclick = function(e) {
                                stop(e);
                                
                                code_editor.destroy();
                                text_editor.destroy();
                                editor.disabled_scroll = false;
                                document.getElementById('edit-card').style.display = 'none';
                        };
                        
                        var sc = document.getElementById('edit-card-start');
                        sc.className = card.id == s.start_id ? 'selected' : '';

                        sc.onmousedown = stop(e);
                        sc.onclick = function(e) {
                                stop(e);
                                
                                sc.className = 'selected';
                        };
                        
                };
                
        },
        links: function() {
                document.getElementById('svg').innerHTML = 
                        '<marker id="triangle" viewBox="0 0 12 12" refX="11" refY="6"  markerUnits="strokeWidth" markerWidth="12" markerHeight="12" orient="auto" fill="#fff">'+
                        '<path d="M 0 0 L 12 6 L 0 12 z" />'+
                        '</marker>';
                
                for (var k = 0; k < s.cards.length; k++) {
                        s.cards[k].out_links = [];
                        s.cards[k].in_links = [];
                }
                        
                for (var k = 0; k < s.cards.length; k++) {
                        for (var i = 0; i < s.cards[k].links.length; i++) {
                                var j = s.find(typeof(s.cards[k].links[i].id) == 'string' ? s.cards[k].links[i].id : s.cards[k].links[i].text);

                                if (j) {
                                        var l = svg.line(document.getElementById('svg'),
                                                           s.cards[k].left + editor.card_width / 2,
                                                           s.cards[k].top + editor.card_height / 2,
                                                                        j.left + editor.card_width / 2,
                                                                        j.top + editor.card_height/2);
                                        
                                        s.cards[k].out_links.push({l:l,j:j});
                                        j.in_links.push({l:l,j:s.cards[k]});
                                } else if (/^https?:/i.test(s.cards[k].links[i].id)) {
                                        
                                } else {
                                        
                                        s.cards.push({id:typeof(s.cards[k].links[i].id) == 'string' ? s.cards[k].links[i].id : s.cards[k].links[i].text,
                                                        text:"",
                                                        left:s.cards[k].left + editor.card_width + 128,
                                                        top:s.cards[k].top,
                                                        out_links:[],
                                                        in_links:[]});
                                        
                                        editor.render(s.cards[s.cards.length - 1]);

                                        var l = svg.line(document.getElementById('svg'),
                                                           s.cards[k].left + editor.card_width / 2,
                                                           s.cards[k].top + editor.card_height / 2,
                                                                        s.cards[s.cards.length - 1].left + editor.card_width / 2,
                                                                        s.cards[s.cards.length - 1].top + editor.card_height / 2);
                                        
                                        s.cards[k].out_links.push({l:l,j:s.cards[s.cards.length - 1]});
                                        s.cards[s.cards.length - 1].in_links.push({l:l,j:s.cards[k]});
                                }
                        }
                }
        },
        edit: function() {
                document.getElementById('svg').innerHTML = 
                        '<marker id="triangle" viewBox="0 0 12 12" refX="11" refY="6"  markerUnits="strokeWidth" markerWidth="12" markerHeight="12" orient="auto" fill="#fff">'+
                        '<path d="M 0 0 L 12 6 L 0 12 z" />'+
                        '</marker>';
                document.getElementById('edit-content').innerHTML = '';
                var ed = document.getElementById('edit');
                var bg = document.getElementById('bg');
                var el = document.getElementById('edit');
                /*
                bg.onmousedown = el.onmousedown = function(e) {
                        stop(e);
                        
                        var ms = dimensions.getMouse(e);
                        
                        document.onmousemove = function(e) {
                                var ms2 = dimensions.getMouse(e);
                                var offsets = dimensions.getOffsets(el);
                                
                                el.style.left = Math.min((offsets.left + (ms2.left - ms.left)), 0) + 'px';
                                el.style.top = Math.min((offsets.top + (ms2.top - ms.top)), 0) + 'px';
                                //dimensions.setScroll(sc.left - (ms2.left - ms.left), sc.top - (ms2.top - ms.top));
                                
                                ms = ms2;
                        };
                        
                        document.onmouseup = function(e) {
                                document.onmousemove = null;
                                document.onmouseup = null;
                        };
                }
                document.onwheel = function(e) {
                        if (editor.disabled_scroll)
                                return;
                        
                        var offsets = dimensions.getOffsets(el);
                        
                        el.style.top = Math.min((offsets.top - (e.deltaY > 0 ? 50 : -50)), 0) + 'px';
                }*/

                for (var j = 0; j < s.cards.length; j++)
                        editor.render(s.cards[j]);
                
                ed.style.width = (editor.max_w + editor.buffer_w) + 'px';
                ed.style.height = (editor.max_h + editor.buffer_h) + 'px';
                                
                editor.links();
        },
        notify: function(message) {
                $.notify({
                        message: message,
                },{
                        type: 'minimalist',
                        delay: 5000,
                        placement: { from:"bottom", align:"right" },
                        animate: { enter: 'animated fadeInUp', exit: 'animated fadeOutDown' },
                        template: '<div data-notify="container" class="col-xs-11 col-sm-3 alert alert-{0}" role="alert">' +
                        // 		'<img data-notify="icon" class="img-circle pull-left">' +
                        // 		'<span data-notify="title">{1}</span>' +
                                        '<span data-notify="message">{2}</span>' +
                                '</div>'                        
                });
        },
        
        create: function() {
                bootbox.prompt("What is the title for your document?", function(res) {
                        if (res != null) {
                                request.post('', {a:'create', title:res}, function(r) {
                                        r = JSON.parse(r);
                                        editor.load(r.id, editor.edit);
                                });
                        }
                });
        },
        remove: function() {
                bootbox.confirm("Are you sure you want to remove this document?  You will not be able to recover it.", function(res) {
                        if (res) {
                                request.post('', {a:'remove', id:s.story_id}, function(r) {
                                        r = JSON.parse(r);
                                        if (r.success) {
                                                document.title = title;
                                                s.story_id = 0;
                                                editor.notify("Your document has been removed.");
                                                
                                                document.getElementById('svg').innerHTML = 
                                                        '<marker id="triangle" viewBox="0 0 12 12" refX="11" refY="6"  markerUnits="strokeWidth" markerWidth="12" markerHeight="12" orient="auto" fill="#fff">'+
                                                        '<path d="M 0 0 L 12 6 L 0 12 z" />'+
                                                        '</marker>';
                                                document.getElementById('edit-content').innerHTML = '';
                                                document.getElementById('edit').onmousedown = stop;
                                                
                                                document.getElementById('save-story').style.display = 
                                                document.getElementById('render-story').style.display =
                                                document.getElementById('publish-story').style.display =
                                                document.getElementById('rename-story').style.display = 
                                                document.getElementById('delete-story').style.display = 'none';
                                        } else
                                                bootbox.alert("There was an error removing your document.");
                                });
                        }
                });
        },
        rename: function() {
                bootbox.prompt({
                        title:"What is the title for your document?",
                        value:s.title,
                        callback: function(res) {
                                if (res != null) {
                                        request.post('', {a:'rename', title:res, id:s.story_id}, function(r) {
                                                r = JSON.parse(r);
                                                
                                                if (r.success) {
                                                        s.title = res;
                                                        document.title = s.title;
                                                        editor.notify("Your document has been updated.");
                                                } else
                                                        bootbox.alert("There was an error updating your document.");
                                        });
                                }
                        }
                });
        },
        list: function() {
                request.post('', {a:'list'}, function(r) {
                        r = JSON.parse(r);
                        bootbox.dialog({
                                title: "Select a document to load.",
                                message: "<select id='list' style='padding:8px 16px;'></select>",
                                buttons: {
                                        cancel: {
                                                label: "Cancel",
                                                className: "btn-default",
                                                callback: function() {
                                                }
                                        },
                                        success: {
                                                label: "Load",
                                                className: "btn-primary",
                                                callback: function() {
                                                        var id = document.getElementById('list').value;
                                                        if (parseInt(id) > 0) {
                                                                editor.load(id, editor.edit);
                                                        }
                                                }
                                        }
                                }
                        });
                        if (r.list.length > 0) {
                                for (var j = 0; j < r.list.length; j++) {
                                        var option = document.createElement('option');
                                        option.innerHTML = r.list[j].title;
                                        option.value = r.list[j].id;
                                        document.getElementById('list').appendChild(option);
                                }
                        } else {
                                var option = document.createElement('option');
                                option.innerHTML = 'No documents have been created.';
                                option.value = '0';
                                document.getElementById('list').appendChild(option);
                        }
                });
        },
        load: function(id, handler) {
                request.post('', {a:'load',id:id}, function(r) {
                        r = JSON.parse(r);
                        
                        if (r.id > 0) {
                                document.getElementById('save-story').style.display = 
                                document.getElementById('render-story').style.display =
                                document.getElementById('publish-story').style.display =
                                document.getElementById('rename-story').style.display = 
                                document.getElementById('delete-story').style.display = 'block';
                                
                                document.getElementById('edit').style.left = '0px';
                                document.getElementById('edit').style.top = '0px';
                                
                                s.story_id = r.id;
                                
                                s.title = r.title;
                                document.title = s.title;
                                
                                jQuery('#publish-toggle').prop('checked', r.published > 0).change();
                                document.getElementById('publish-link').href = 'story/'+s.story_id;
                                
                                var temp = JSON.parse(r.json);
                                s.start_id = temp.start_id;
                                s.cards = temp.cards;
                                
                                if (typeof(handler) == 'function')
                                        handler();
                        } else {
                                document.getElementById('save-story').style.display = 
                                document.getElementById('render-story').style.display =
                                document.getElementById('publish-story').style.display =
                                document.getElementById('rename-story').style.display = 
                                document.getElementById('delete-story').style.display = 'none';
                                
                                s.story_id = 0;
                        }
                });
        },
        save: function(handler) {
                var cards = [];
                for (var j = 0; j < s.cards.length; j++) {
                        cards.push({
                                        id:s.cards[j].id,
                                        code:s.cards[j].code,
                                        text:s.cards[j].text,
                                        left:s.cards[j].left,
                                        top:s.cards[j].top
                                });
                }
                var temp = {
                        start_id:s.start_id,
                        cards:cards
                };
                request.post('', {a:'save', json:JSON.stringify(temp), id:s.story_id}, function(r) {
                        r = JSON.parse(r);

                        if (r.success) {
                                if (typeof(handler) == 'function')
                                        handler();

                                editor.notify("Your document has been saved.");
//                                        bootbox.alert("Your document has been saved.");
                        } else {
                                bootbox.alert("There was an error saving your document.");
                        }
                        
                });
        }
};

