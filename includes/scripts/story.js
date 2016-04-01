var story = {
        spid: 0,

        random: null,
        clicks:[],
        pclicks:[],
        performclick:null,

        id: null, card: null, div: null, links: [],                     // current card
        captions: [],
        cycles: [],

        markedStrip: function(text) {
                return typeof(text) == 'string' ? marked(text).replace(/^<p>/, '').replace(/<\/p>\n$/, '') : "";
        },
        findCaption: function(id) {
                for (var j = 0; j < story.captions.length; j++) {
                        if (story.captions[j].spid == id) {
                                return story.captions[j].caption;
                        }
                }
                return "";
        },
        findCycle: function(id) {
                for (var j = 0; j < story.cycles.length; j++) {
                        if (story.cycles[j].spid == id) {
                                return story.cycles[j];
                        }
                }
                return {};
        },
        followLink: function(link) {
                s.previous_id = story.card.id;
                s.previous_text = story.card.selected_text = link.textContent;

                while (story.links.length > 0) {
//                for (var i = 0; i < links.length; i++) {
                        var span = document.createElement('span');
                        span.innerHTML = story.links[0].innerHTML;
                        story.links[0].parentNode.insertBefore(span, story.links[0]);
                        story.links[0].parentNode.removeChild(story.links[0]);
  //                      i--;
//                        links[i].className = links[i] != link ? 'disabled' : 'enabled';
  //                      links[i].onmousedown = links[i].onclick = stop;
                }
                story.div.className = 'previous';
//                story.div.parentNode.removeChild(story.div);
                
                story.performclick = null;
                
                story.clicks.push({type:'link', text:link.textContent, id:link.getAttribute('href')});
                localStorage.setItem("clicks-"+s.story_id, JSON.stringify(story.clicks));
                
                story.render(link.getAttribute('href'));
        },
        setupLink: function(link) {
                link.onmousedown = stop;
                link.onclick = function(e) {
                        stop(e);

                        if (s.find(link.getAttribute('href')))
                                story.followLink(link);
                        else if (/^https?:/gi.test(link.getAttribute('href')))
                                window.open(link.href, '_system');
                        else
                                bootbox.alert("The content for this link has not been defined.");
                };
        },
        
        setupPopover: function(link, caption) {
                link.onclick = stop;
                jQuery(link).popover({animation:true, content:marked(story.findCaption(link.getAttribute('id'))), placement:'bottom', trigger:'focus', html:true});
        },
        
        followCycle: function(link) {
                var cycle = story.findCycle(link.getAttribute('id'));
                
                cycle.current++;
                if (cycle.current > cycle.options.length - 1 && !cycle.is_sequence)
                        cycle.current = 0;
                else if (cycle.current > cycle.options.length - 1)
                        cycle.current = cycle.options.length - 1;
                        
                s.set(cycle.id, cycle.options[cycle.current]);

                
                if (cycle.current == cycle.options.length - 1 && cycle.is_sequence) {
                        var p = link.parentNode;
                        p.innerHTML = story.markedStrip(cycle.options[cycle.current]);
                        
                        var els = p.getElementsByTagName('a');
                        for (var j = 0; j < els.length; j++) {
                                if (els[j].className != 'caption' && els[j].className != 'cycle')
                                        story.setupLink(els[j]);
                        }
                } else {
                        link.innerHTML = story.markedStrip(cycle.options[cycle.current]);
                        
                        var els = link.getElementsByTagName('a');
                        if (els.length > 0) {
                                var p = link.parentNode;
                                p.innerHTML = link.innerHTML;
                                var els = p.getElementsByTagName('a');
                                for (var j = 0; j < els.length; j++)
                                        if (els[j].className != 'caption' && els[j].className != 'cycle')
                                                story.setupLink(els[j]);
                        }
                }
                
                story.clicks.push({type:'cycle', spid:link.getAttribute('id'), id:cycle.id});
                localStorage.setItem("clicks-"+s.story_id, JSON.stringify(story.clicks));
        },
        setupCycle: function(link) {
                var cycle = story.findCycle(link.getAttribute('id'));
                
                cycle.value = s.get(cycle.id);
                if (!cycle.value) {
                        s.set(cycle.id, cycle.options[0]);
                        cycle.value = cycle.options[0];
                }
                
                cycle.current = -1;
                link.onmousedown = stop;
                
                for (var j = 0; j < cycle.options.length; j++) {
                        if (cycle.options[j] == cycle.value) {
                                cycle.current = j;
                                break;
                        }
                }
                if (cycle.current < 0)
                        cycle.current = cycle.options.length - 1;

                link.onclick = function(e) {
                        stop(e);
                        story.followCycle(link);
                };
                
                link.innerHTML = story.markedStrip(cycle.value);
                
                var els = link.parentNode.getElementsByTagName('a');
                for (var j = 0; j < els.length; j++)
                        if (els[j].className != 'caption' && els[j].className != 'cycle')
                                story.setupLink(els[j]);
        },
                
        start: function(id) {
                if (document.getElementById('settings')) {
                        document.getElementById('settings').onclick = function(e) {
                                stop(e);
                                bootbox.confirm("Start from the beginning?", function(res) {
                                        if (res) {
                                                localStorage.setItem("random-"+s.story_id, Math.random());
                                                localStorage.setItem("clicks-"+s.story_id, '[]');
                                                story.start(s.start_id);
                                        }
                                });
                        };
                }
                
                
                s.start();

                
                
                
                // stored random seed
                story.random = localStorage.getItem("random-"+s.story_id);
                story.random = (typeof(story.random) == "string" && story.random != 'undefined' && story.random.length > 0) ? parseFloat(story.random) : Math.random();
                localStorage.setItem("random-"+s.story_id, story.random);
                Math.seedrandom(story.random)


                
                
                // stored clicks
                story.pclicks = localStorage.getItem("clicks-"+s.story_id);
                story.pclicks = (typeof(story.pclicks) == "string" && story.pclicks != 'undefined' && story.pclicks.length > 0) ? JSON.parse(story.pclicks) : [];
                localStorage.setItem("clicks-"+s.story_id, JSON.stringify(story.pclicks));
                story.clicks = [];


                
                story.render(id);
                
                while (story.performclick != null)
                        story.followLink(story.performclick.link);
        },
        render: function(id) {
                s.id = story.id = id;
                s.card = story.card = s.find(id);
                
                
                
                try {
                        eval(story.card.code);
                } catch(e) {
                        bootbox.alert({
                                title:"JavaScript Error",
                                message:e
                        });
                }
                
                
                
                story.div = document.createElement('div');
                story.div.style.opacity = 0;
                s.container.appendChild(story.div);
                        
                
                
                
                
                var result = story.card.text;
                
                story.captions = [];
                story.cycles = [];
                
                // evaluate embedded variables --------- <<variable>>
                while (matches = /\{\{([^\{\}]+?)\}\}/g.exec(result)) {
                        if (/^random\:/.test(matches[1])) {
                                var temp = matches[1].replace(/^random\:/i, '');
                                temp = temp.split('->');
                                s.set(temp[0], s.pick(temp.slice(1)));
                                result = result.replace(matches[0], story.markedStrip(s.get(temp[0])));
                                
                        } else if (/^caption\:/.test(matches[1])) {
                                var temp = matches[1].replace(/^caption\:/i, '');
                                temp = temp.split('->');
                                story.captions.push({spid:"spid-"+(++story.spid),text:temp[0],caption:temp[1]});
                                result = result.replace(matches[0], '<a href="#" class="caption" id="spid-'+story.spid+'">'+story.markedStrip(temp[0])+'</a>');
                                
                        } else if (/^cycle\:/.test(matches[1])) {
                                var temp = matches[1].replace(/^cycle\:/i, '');
                                temp = temp.split('->');
                                var id = temp[0];
                                var options = temp.slice(1);
                                story.cycles.push({spid:"spid-"+(++story.spid),id:id,options:options,is_sequence:false});
                                result = result.replace(matches[0], '<a href="#" class="cycle" id="spid-'+story.spid+'">'+'</a>');
                                
                        } else if (/^sequence\:/.test(matches[1])) {
                                var temp = matches[1].replace(/^sequence\:/i, '');
                                temp = temp.split('->');
                                var id = temp[0];
                                var options = temp.slice(1);
                                story.cycles.push({spid:"spid-"+(++story.spid),id:id,options:options,is_sequence:true});
                                result = result.replace(matches[0], '<span><a href="#" class="cycle" id="spid-'+story.spid+'">'+'</a></span>');
                                
                        } else {
                                var temp = "";
                                try {
                                        temp = eval(matches[1]);
                                } catch(e) {
                                        temp = "";
                                }
                                result = result.replace(matches[0], story.markedStrip(temp.toString()));
                        }
                }

                // convert text to javascript s.print statement -------- >>text<<
                while (matches = />>([\s\S]*?)<</g.exec(result)) {
                        var code = "\ts.print("+JSON.stringify(matches[1])+");\n";
                        result = result.replace(matches[0], code);
                }

                // evaluate javascript code <<code>>
                while (matches = /<<([\s\S]*?)>>(\s*?\n?)/g.exec(result)) {
                        s.output = '';
                        try {
                                eval(matches[1]);
                        } catch(e) {
                                bootbox.alert({title:"JavaScript Error", message:e });
                        }
                        result = result.replace(matches[0], s.output + matches[2]);
                }
                

                
                // render result
                story.div.innerHTML = marked(result);

                // parse links
                story.links = story.div.getElementsByTagName('a');
                for (var j = 0; j < story.links.length; j++) {
                        if (story.links[j].className == 'caption') {
                                story.setupPopover(story.links[j]);
                        } else if (story.links[j].className == 'cycle') {
                                story.setupCycle(story.links[j]);
                        } else
                                story.setupLink(story.links[j]);
                }

                // table classes
                var tables = story.div.getElementsByTagName('table');
                for (var j = 0; j < tables.length; j++)
                        tables[j].className = 'table table-bordered table-condensed';
                

                
                // handle saved clicks
                var c = null;
                if (story.pclicks.length > 0) {
                        c = story.pclicks[0];
                        story.pclicks = story.pclicks.slice(1);
                        
                        if (c.type == 'cycle') {
                                do {
                                        for (var j = 0; j < story.links.length; j++) {
                                                if (story.links[j].className == 'cycle') {
                                                        if (c.spid == story.links[j].getAttribute('id')) {
                                                                story.followCycle(story.links[j]);
                                                        }
                                                }
                                        }
                                        if (story.pclicks.length > 0) {
                                                c = story.pclicks[0];
                                                story.pclicks = story.pclicks.slice(1);
                                        } else {
                                                c = null;
                                        }
                                } while (c && c.type == 'cycle');
                        }
                        
                        if (c && c.type == 'link') {
                                for (var j = 0; j < story.links.length; j++) {
                                        if (story.links[j].className != 'caption' && story.links[j].className != 'cycle') {
                                                if (c.id == story.links[j].getAttribute('href') && c.text == story.links[j].textContent) {
                                                        story.performclick = {  link: story.links[j] };
                                                }
                                        }
                                }
                        }
                }
                
                
                
                animate(story.div.style, 'opacity', '', 0, 1, 500);

                if (!s.animate) {
                        return;
                }

                
                /*if (s.start_id == id && !c) {
                        document.body.scrollTop = 0;//dimensions.setScroll(0, 0);
                        
                } else*/
                if (!c) {
                        var scel = dimensions.getScroll();
                        var of = dimensions.getOffsetsWithScroll(story.div);
                        
                        animate(document.body, 'scrollTop', '', scel.top, of.top - 24, 1000);
                }
        }
};
