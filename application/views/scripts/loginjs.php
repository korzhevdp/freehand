$(".logIn").click(function () {
	$("#uploadError").addClass("hide");
	$("#loginM").modal("show");
});

$("#doNotReg").click(function(){
	$("#password2span, #regWelcome, #tryRegIn, #logAlert").addClass("hide");
	$("#tryLogIn").removeClass("hide");
});

$("#tryLogIn").click(function(){
	$.ajax({
		url          : "/locallogin/checkuser",
		type         : "POST",
		data         : {
			login    : $("#login").val(),
			password : $("#password").val(),
		},
		dataType     : 'script',
		success: function () {
			if (parseInt(logresult.status, 10) === 0) {
				$("#password2span, #regWelcome, #tryRegIn").removeClass("hide");
				$("#tryLogIn, #logAlert").addClass("hide");
				$("#wrongPass").empty().html(logresult.error).dequeue().fadeIn(50).delay(4000).fadeOut(500);
				return false;
			}
			if (parseInt(logresult.status, 10) === 2) {
				$("#wrongPass").empty().html(logresult.error).dequeue().fadeIn(50).delay(4000).fadeOut(500);
				return false;
			}
			$("#userP").html(logresult.login + '&nbsp;&nbsp;<i class="icon-user"></i>');
			mp.nav = logresult.nav;
			$("#password2span, #regWelcome, #tryRegIn, #logOut").removeClass("hide");
			$("#tryLogIn, #logAlert, #logIn").addClass("hide");
			$("#loginM").modal("hide");
		},
		error: function (data, stat, err) {
			console.log([ data, stat, err ]);
		}
	});
});

$("#tryRegIn").click(function(){
	$.ajax({
		url           : "/locallogin/adduser",
		type          : "POST",
		data          : {
			login     : $("#login").val(),
			password  : $("#password").val(),
			password2 : $("#password2").val()
		},
		dataType      : 'script',
		success: function () {
			if (regresult.status == "1") {
				$("#userP").html(regresult.login);
				$("#loginM").modal("hide");
				$("#password2span, #regWelcome, #tryRegIn, #logOut").removeClass("hide");
				$("#tryLogIn, #logAlert, #logIn").addClass("hide");
				$("#loginM").modal("hide");
				return true;
			}
			if (regresult.status == "0") {
				console.log(regresult.error);
				return false;
			}
			//resetSession();
		},
		error: function (data, stat, err) {
			console.log([ data, stat, err ]);
		}
	});
});

function getUser(){
	$.ajax({
		type     : "POST",
		url      : "/freehand/getuserdata",
		dataType : 'script',
		success  : function () {
			$("#userP").html(logindata.name + '&nbsp;&nbsp;' + logindata.photo).attr('title', logindata.title);
			if (logindata.name === "Гость") {
				$(".logIn").removeClass("hide");
				$(".logOut").addClass("hide");
				return false;
			}
			$(".logOut").removeClass("hide");
			$(".logIn").addClass("hide");
			userUID = logindata.uid;
		},
		error: function(data,stat,err){
			alert([data,stat,err].join("\n"));
		}
	});
}

getUser();