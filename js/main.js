
"use strict";

$.mobile.autoInitializePage = false;

if ([].indexOf) {
  var find = function(array, value) {
    return array.indexOf(value);
  }
} else {
  var find = function(array, value) {
    for (var i = 0; i < array.length; i++) {
      if (array[i] === value) return i;
    }
    return -1;
  }
}

function findDir(dirs, path) {
	for(var i=0; i<dirs.length; i++) {
		if(dirs[i].fullpath == path) return dirs[i];
		if(dirs[i].children.length > 0) { //has subfolders
			var ret;
			if(ret = findDir(dirs[i].children, path)) return ret;
		}
	}
	return null;
}

function formList (previousValue, element, index) {
	// statements
	var now = new Date();
	var date_opts = date_opts_past;
	if(now.getFullYear() == element['Received-Date'].getFullYear()) {
		date_opts = date_opts_this_year;
			if(now.getDate() == element['Received-Date'].getDate() &&
				 now.getMonth() == element['Received-Date'].getMonth()) date_opts = date_opts_today;
	}
            //message-list
	previousValue += '<input type=checkbox class="chooser-chk" id="' + element.uid +'">';
	previousValue += '<div class="row my-2 align-items-center d-flex align-items-stretch' + (element.new?' new':'') + '" uid="' + element.uid + '">';
	previousValue += '<div class="col-auto mess_logo mx-1 mx-md-2"><label class="pillow d-flex align-items-center justify-content-center" for="' + element.uid + '">' + element.From.split('"').join('')[0].toUpperCase() + '</label></div>';
	previousValue += '<div class="d-flex col-3 col-md-2 mx-1 mx-md-2 px-1 px-md-2 mfrom align-items-center"><div class="text-truncate">' + element.From + '</div></div>';
	previousValue += '<div class="d-flex col-5 col-md-7 col-xl-8 mx-1 mx-md-2 px-1 px-md-2 msubject align-items-center"><div class="text-truncate">';
	if(element.attachments.length) {
		previousValue += '&#128206; ';
	}
	previousValue += (element.Subject?element.Subject:"") + '</div></div>';
	previousValue += '<div class="col col-md-1 px-1 d-flex align-items-center mdate">' + element['Received-Date'].toLocaleString("ru",date_opts) + '</div>';
	previousValue += '</div>';
	return previousValue;
}


function mailBox() { //main class for working
	this.dirs = [];
	this.cur_box = {};
	var me = this;
	this.mess_list_loaded = [];
	this.checkedMessages = [];
	this.listCount = 20;
	this.listViewed = this.listCount;

	function makeDirs(dirsObj, ul_id, container) {
		var ulCont = container.appendChild(document.createElement('ul'));
		ulCont.id = ul_id;
		ulCont.className = "list-unstyled ";
		ulCont.className += (container.nodeName == "LI") ? "collapse" : "component";
		dirsObj.forEach( function(element, index) {
			var liCont = ulCont.appendChild(document.createElement('li'));
			var node = liCont.appendChild(document.createElement('a'));
			node.innerHTML = element.name;
			if(+element.unseen) {
				$(node).addClass("unseen");
				$(node).append(" (" + element.unseen + ")");
			}
//			node.href = "#";
//			node.id = ul_id + index;
			node.setAttribute("boxpath",element.fullpath);
			$(node).on('click', function () {
				$("a.selected").removeClass("selected");
				$(this).addClass("selected");
				$("#foldername_header").html(this.innerHTML);
				if(me.cur_box.fullpath != this.getAttribute("boxpath")) {
					var newbox;
					if(newbox = findDir(me.dirs, this.getAttribute("boxpath"))) {
						me.openBox(newbox); //open message list from the 
					}
				}
			});
			if(!(~element.options.indexOf('HasNoChildren'))) {
				$(node).addClass("dropdown-toggle");
				node.setAttribute("data-toggle","collapse");
				node.setAttribute("aria-expanded","false");
				node.href = "#" + "sub" + ul_id + "_" + index;
				makeDirs(element.children, "sub" + ul_id + "_" + index, liCont);
			}
		});
	}

	this.dirTree = function(dir_js) {
		this.dirs = dir_js;

		this.dirs.sort( function(a,b) {
		//	if( a.name > b.name ) return 1;
			if( b.name == "INBOX" ) return 1;
			return -1;
		});
	    makeDirs(this.dirs,"rootMenu", document.getElementById("sidebar"));
	}
};

mailBox.prototype.setListEvents = function () {
	let me = this;
	this.mailContent = {};
	$('[uid]').prop("onclick", null).off("click");
	$('[checkall]').prop("onclick", null).off("click");
	$('.pillow').prop("onclick", null).off("click");

	$("[uid]").on('click', function (){
		$("#messages").html("");
		$("#messages").hide(0);
//		alert(me.cur_box.fullpath + " " + this.getAttribute("uid"));
		let contentRequest = {
			action: "viewMessage",
			boxpath: me.cur_box.fullpath,
			uid: this.getAttribute("uid")
		}
		$.ajax({
			type: "POST",
			url: document.URL,
			data: JSON.stringify(contentRequest),
			contentType: "application/json; charset=utf-8",
			dataType: "json",
			success: function(result) { 
//				alert(JSON.stringify(result.headers));
//				alert(JSON.stringify(result.body));
//				alert(JSON.stringify(result.attaches[0]));
				me.mailContent = result;
				$("#mailreader").show(0);
				var mailreader = document.getElementById("mailreader-headers");
				mailreader.firstElementChild.firstElementChild.firstElementChild.textContent = me.mailContent.headers["Subject"];
//				alert(me.mailContent.headers["From"]);
				mailreader.firstElementChild.children[1].firstElementChild.textContent = me.mailContent.headers["From"];
				var mailcontent = document.getElementById("mailreader-content");
//				var ifr = document.createElement('iframe');
//				ifr.setAttribute("srcdoc", me.mailContent.body);
//				ifr.setAttribute("seamless", "");
				mailcontent.firstElementChild.innerHTML = me.mailContent.body;
				var buttons = document.getElementById("messagebtns");
				if(document.getElementById('attacheslist')) {
					buttons.removeChild(document.getElementById('attacheslist'));
				}
				if("attaches" in me.mailContent && me.mailContent.attaches.length > 0) {
                    var attachbutton = document.createElement('div');
                    attachbutton.classList.add("btn-group");
                    attachbutton.classList.add("dropleft");
                    attachbutton.id = "attacheslist";
                    var btnContent = '<button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">&#128206;</button><div class="dropdown-menu">';
					for(let i=0; i<me.mailContent.attaches.length; i++) {
						btnContent = btnContent + '<a class="dropdown-item" href="?type=attach&name=' + me.mailContent.attaches[i] + '">' + me.mailContent.attaches[i] + '</a>';
					}
					btnContent = btnContent + '</div>';
					attachbutton.innerHTML = btnContent;
					buttons.prepend(attachbutton);
				}
//				alert(mailcontent.firstElementChild.getElementsByTagName("style")[0].innerHTML);
//				mailcontent.firstElementChild.textContent = me.mailContent.body;
//				mailcontent.firstElementChild.innerHTML = "<img src = 'https://r.mradx.net/img/F0/C011D6.png'>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";
				me.cur_box = {};
			},
//			failure: function(errMsg) { alert(errMsg); }
			error: function (xhr, ajaxOptions, thrownError) {
        		alert(xhr.status);
        		alert(thrownError);
				me.cur_box = {};
      		}
		});		
	});

	$("[checkall]").on("click", function () {
		me.allChecked = !me.allChecked;
		me.checkedMessages = [];
		me.mess_list_loaded.forEach(function(element, index) {
			if(me.allChecked) me.checkedMessages.push(element.uid);
//				else me.checkedMessages.pop();
		});
		$(".chooser-chk").prop("checked", me.allChecked);
		alert(me.checkedMessages);
		event.stopPropagation();
	});

	$(".pillow").on("click", function() {
		var uid = this.getAttribute("for");
		var index;
		if(~(index = find(me.checkedMessages,uid))) {
			me.checkedMessages.splice(index,1);
		}
		else me.checkedMessages.push(uid);
		alert(me.checkedMessages);
		event.stopPropagation();
	});
};

mailBox.prototype.appendList = function () {
	let me = this;
	let addedList = "";
	var startFrom = this.firstMailNum - this.listViewed;
	var getListRequest = {
		action: "getBoxListNext",
		boxpath: this.cur_box.fullpath,
		rowCount: this.listCount,
		start: startFrom
	};
	$.ajax({
		type: "POST",
		url: document.URL,
		data: JSON.stringify(getListRequest),
		contentType: "application/json; charset=utf-8",
		dataType: "json",
		success: function(result) { 
			result.list.forEach(function(item,i,arr) {
				item['Received-Date'] = new Date( item['Received-Date'] );
				for(var key in item) {
					if(typeof(item[key]) == 'string') {
						item[key] = item[key].replace(/</g, '&lt;').replace(/>/g, '&gt;');
					}	
				}
			});
			addedList = result.list.reduce(formList,"");
			me.listViewed += me.listCount;
			me.mess_list_loaded = me.mess_list_loaded.concat(result.list);
			$('#messages').append(addedList);	
			if( me.full_count > me.listViewed ) {
				$('#morebutton').appendTo("#messages");
			}
			else $('#morebutton').remove();
			me.setListEvents();
		},
		failure: function(errMsg) { alert(errMsg); }
	});
};

mailBox.prototype.printList = function(m_list) {

		let me = this;
		this.mess_list_loaded = m_list.list;
		this.full_count = m_list.fullCount;
		this.listViewed = this.listCount; //setting to  start value 
		var messcontent="";
			//list legend
		messcontent += '<div class="row my-2 align-items-center d-flex align-items-stretch">';
        messcontent += '<div class="col-auto list_legend d-flex justify-content-center mx-1 mx-md-2" data-toggle="tooltip" title="check all" checkall>&#9745;</div>';
        messcontent += '<div class="d-flex col-3 col-md-2 mx-1 mx-md-2 px-1 px-md-2 list_legend align-items-center">from</div>';
        messcontent += '<div class="d-flex col-5 col-md-7 col-xl-8 mx-1 mx-md-2 px-1 px-md-2 list_legend align-items-center">subject</div>';
        messcontent += '<div class="col col-md-1 px-1 d-flex align-items-center list_legend text-truncate">date</div></div>';
		messcontent += this.mess_list_loaded.reduce(formList,"");
		if(!this.mess_list_loaded.length) { //empty list
			messcontent += '<div class="row my-2 align-items-center d-flex align-items-stretch"><div class="d-flex col mx-1 px-1 mfrom align-items-center justify-content-center">emtpy list</div></div>';
		}

		$(".ph-item").hide(0);
		$("#mailreader").hide(0);
		var mailcontent = document.getElementById("mailreader-content");
		mailcontent.firstElementChild.innerHTML = "";
		$("#messages").html(messcontent);
		if( me.full_count > me.listCount ) {
			$('#messages').append('<button type="button" id="morebutton" class="btn">more...</button>');
			$('#morebutton').on("click", function() {
				me.appendList();
			});
		}
		$("#messages").show(0)
		me.setListEvents();
}


mailBox.prototype.openBox = function(boxObj) {

	var me = this;
	this.cur_box = boxObj;
	this.allChecked = false;
	messages.innerHTML = "";
	$(".ph-item").show(0);

/*	var query_json = {
		opertaion: 'getList',
		boxName: me.cur_box.fullpath
	};*/

//	var json_str1 = prompt("Введите строку", "");//'{"From":"Herrrr"}';
	var getListRequest = {
		action: "getBoxList",
		boxpath: me.cur_box.fullpath,
		rowCount: me.listCount
	};
	$.ajax({
			type: "POST",
			url: document.URL,
			data: JSON.stringify(getListRequest),
			contentType: "application/json; charset=utf-8",
			dataType: "json",
			success: function(result) { 
/*				var list = result.list;
		me.mess_list_loaded = JSON.parse(list.replace(/</g, '&lt;').replace(/>/g, '&gt;'), function (key, value) {
			if( key == 'Received-Date') return new Date( value );
			return value;
		});*/	
				result.list.forEach(function(item,i,arr) {
					item['Received-Date'] = new Date( item['Received-Date'] );
					for(var key in item) {
						if(typeof(item[key]) == 'string') {
							item[key] = item[key].replace(/</g, '&lt;').replace(/>/g, '&gt;');
						}	
					}
				});
				me.firstMailNum = result.first;
				me.printList(result);
			},
			failure: function(errMsg) { alert(errMsg); }
		});
//	printList(json_str1);
/*	$.post("http://sasha.modem.ru:8081/1.php","",printList,"");*/
};



var box;
var date_options = { day: "numeric", month: "short", year: "numeric", hour: "numeric", minute: "numeric"};
var date_opts_today = { hour: "numeric", minute: "numeric"};
var date_opts_this_year = { day: "numeric", month: "short"};
var date_opts_past = { day: "numeric", month: "numeric", year: "numeric"};


$(document).ready(function () {

    $('#sidebarCollapse').on('click', function () {
        $('#sidebar').toggleClass('active');
    });
    $(document).on("swiperight", function(event) {
    	if( !$('#sidebar').hasClass('active') ) {
    		$('#sidebar').addClass('active');
    	}
    });
    $(document).on("swipeleft", function(event) {
    	if( $('#sidebar').hasClass('active') ) {
    		$('#sidebar').removeClass('active');
    	}
    });
    $("#mailreader").hide(0);
    $('#logoutbutton').on("click", function() {
    	let logoutrequest = {
    			action: "logout"
    	};
		$.ajax({
				type: "POST",
				url: document.URL,
				data: JSON.stringify(logoutrequest),
				contentType: "application/json; charset=utf-8",
				dataType: "json",
				success: function(resp) { 
					if(resp.logout) {
						window.location = window.location.toString();
					}
	 			},
				failure: function(errMsg) { alert(errMsg); }
			});
    });
    $('[data-toggle="tooltip"]').tooltip();
    box = new mailBox();
    var request = {
    	action: "get_dir_tree"
    };
	$.ajax({
			type: "POST",
			url: document.URL,
			data: JSON.stringify(request),
			contentType: "application/json; charset=utf-8",
			dataType: "json",
			success: function(dirs) { 
				var current;
				box.dirTree(dirs);
				if(!~dirs.findIndex(dir=>dir.name.toUpperCase() == 'INBOX')) {
					current = dirs[0];
				}
			    else current = dirs.find(dir=>dir.name.toUpperCase() == 'INBOX');
			    box.openBox(current);
			    $("#foldername_header").html(current.name + " (" + current.unseen + ")");
 			},
			failure: function(errMsg) { alert(errMsg); }
		});
//    box.dirTree(dirs_json);
});

/*var mailContent = {
	headers: [],
	from: null,
	subject: null,
	date: null,
	body: null
};

var messPreview = {
	uid: 1020102,
	from: "email",
	date: "date",
	subject: "string",
	attachments: []
}*/