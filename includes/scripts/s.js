var o = {

};

var s = {
        story_id: null, title: null, start_id: null, cards: [],
        
        container: null,
        animate:true,
        
        id:null, card:null,
        previous_id:null, previous_text:null,
        
        find: function(id) {
                for (var j = 0; j < s.cards.length; j++)
                        if (s.cards[j].id == id)
                                return s.cards[j];
                return null;
        },
        
        start: function() {
                s.container.innerHTML = '';
                
                o = {};
                
                s.id = null;
                s.card = null;
                s.previous_id = null;
                s.previous_text = null;
                
                s.genders = ["male","female"];
                s.family_names = ["Smith","Johnson","Williams","Brown","Jones","Miller","Davis"];
                s.male_names = ["James","John","Robert","Michael","William","David","Richard"];
                s.female_names = ["Mary","Patricia","Linda","Barbara","Elizabeth","Jennifer","Maria"];
                s.eye_colors = ["Brown","Blue","Green","Grey","Hazel"];
                s.hair_colors = ["Black","Brown","Blonde","Auburn","Chestnut","Red","Gray","White"];
        },
        
        output: '',
        print: function(str) {
                s.output += str;
        },
        clear: function() {
                s.container.innerHTML = '';
        },
        
        
        
        
        
        
        set: function(id, value) {
                var set = false;
                if (typeof(o.___values) == 'undefined')
                        o.___values = [];
                for (var j = 0; j < o.___values.length; j++) {
                        if (o.___values[j].id == id) {
                                o.___values[j].value = value;
                                set = true;
                                break;
                        }
                }
                if (!set)
                        o.___values.push({id:id,value:value});
        },
        get: function(id) {
                if (typeof(o.___values) == 'undefined')
                        return null;
                for (var j = 0; j < o.___values.length; j++) {
                        if (o.___values[j].id == id) {
                                return o.___values[j].value;
                        }
                }
                return null;
        },
        
        
        
        
        
        
        pick: function(arr) {
                return arr[Math.floor(Math.random() * arr.length)];
        },
        grab: function(arr) {
                var i = Math.floor(Math.random() * arr.length);
                return (arr.splice(i, 1))[0];
        },
        randomString: function(length) {
                var text = "";
                var possible = "abcdefghijklmnopqrstuvwxyz0123456789";

                for (var i = 0; i < length; i++)
                        text += possible.charAt(Math.floor(Math.random() * possible.length));

                return text;
        },
        
        
        
        
        
        
        
        createCharacter: function(id, role) {
                o[id] = {};
                o[id].id = s.randomString(12);
                
                o[id].gender = s.pick(s.genders);
                o[id].man = o[id].gender == "male" ? "man" : "woman";
                o[id].boy = o[id].gender == "male" ? "boy" : "girl";
                
                o[id].name = s.grab(o[id].gender == "male" ? s.male_names : s.female_names);
                o[id].family_name = s.grab(s.family_names);
                
                o[id].eye_color = s.pick(s.eye_colors);
                o[id].hair_color = s.pick(s.hair_colors);
                
                o[id].role = role;
        }
}