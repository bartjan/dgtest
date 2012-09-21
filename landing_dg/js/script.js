$(document).ready(function() {  
	function resizePanel() {
		width = $(window).width();
		height = $(window).height();
		mask_width = width * $('.item').length;
		$('#wrapper, .item').css({width: width, height: height});
		$('#mask').css({width: mask_width, height: height});
		$('#wrapper').scrollTo($('a.selected').attr('href'), 0);
		content_h=$('.content').height();
		margintop=Math.floor((height-content_h)/2-20);
		$('.content').css({'margin-top':margintop+'px'})
			
	}
	
	$.fn.extend({
			initilizeAll:function(){
				var austDay = new Date();
				//HERE TO EDIT
				austDay = new Date(2012,9-1,1);
				// END EDIT
				$('#defaultCountdown').countdown({until: austDay});

				var content_h=$('.content').height();
				var winH = $(window).height();
				var margintop=Math.floor((winH-content_h)/2-20);
				$('.content').css({'margin-top':margintop+'px'})
				
				var litem=$('.linkcontainer'),
					ltemp=0;
				litem.children('.ico').each(function(){
					ltemp=ltemp+$(this).width();
				})
				litem.css({'width':ltemp})
				
		},
		ItemAnimate:function(){
			return this.each(function(i){
				$(this).mouseenter(function(){
					$(this).find('.itop').stop(true,true).animate({'top':'-5px'},300);
					$(this).find('.ibottom').stop(true,true).animate({'top':'5px'},300);
				})
				$(this).mouseleave(function(){
					$(this).find('.itop').stop(true,true).animate({'top':'0px'},400);
					$(this).find('.ibottom').stop(true,true).animate({'top':'0px'},400);
				})
			})
		}


	})
	
	
	$('body').initilizeAll()
	
	$('.scroll-pane').jScrollPane({
		verticalDragMinHeight: 53,
		verticalDragMaxHeight: 53
	});
	
	//INIT TOOLTIP
	$('.ico').ItemAnimate()
	
	
	$('a.nav').add('a.init').click(function (event, speed) {
		$('a.nav').removeClass('selected');
		$(this).addClass('selected');
		current = $(this);
		if (speed!=1)
			speed=800
		$('#wrapper').scrollTo($(this).attr('href'), speed);		
		return false;
	});
	
	$('a.init').trigger('click',[1])
										
	
	$(window).resize(function () {
		resizePanel();
	});

/*=====================================FORM VALDIATION==========================================*/
	//Clear form
	function clear_form_elements(ele) {
		ele.find(':input').each(function() {
			switch(this.type) {
				case 'password':
				case 'select-multiple':
				case 'select-one':
				case 'text':
				case 'textarea':
					$(this).val('');
					break;
				case 'checkbox':
				case 'radio':
					this.checked = false;
			}
		});
	}


	var error = false;
	
	var regex_email = /[A-Za-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[A-Za-z0-9!\#$%&'*+\/=?^_`{|}~-]+)*@(?:[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?\.)+[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?/;		

	//FORM FIELDS
		var name		=$('#name'),
			email		=$('#email'),
			subject		=$('#subject'),
			msg			=$('#message'),
			frmresult	=$('#frmresult')
			;
			
	
		var cleanErrorMsgs = function() {
			$('.haserror').removeClass('haserror');
			frmresult.removeClass();
		};
		
		var showErr_inputSome = function(inputid,errmsg) {
			if (!inputid.hasClass('haserror')){
				inputid.closest('li').addClass('haserror');
				error = true; 
			}
		};

		//FORM AJAX PROCESS
		var ajaxprocess=function(vfrm,vpost_url){
			var formData = vfrm.serialize();
				$.ajax({
					url:vpost_url,
					type:'post',
					data:formData,
					success: function(msg){
						// if (msg==1){
							clear_form_elements(vfrm);
							cleanErrorMsgs();
							frmresult.addClass('valid').html('<p>Uw contact aanvraag is aangekomen!</p>').fadeIn(500);
						// } 
						// else{
						// 	frmresult.addClass('error').html('<p>Contact backend programmer!</p>').fadeIn(500);
						// }
					}
				});
			return false;
		}

//contact form
/*			name		=$('#name'),
			phone		=$('#phone'),
			addr		=$('#addr'),
			email		=$('#email'),
			radio		=$('input[type="radio"]');
*/
$('#frmcontact').submit(function(e){
		cleanErrorMsgs();
		var frm = $(this),
			post_url=frm.attr('action');
			error = false;
			
		if (name.val() == '')										showErr_inputSome(name);
		if (email.val() == '' ||  !regex_email.test(email.val()))	showErr_inputSome(email);
		if (subject.val() == '')									showErr_inputSome(subject);
		if (msg.val() == '')										showErr_inputSome(msg);
		if (error == false){
			ajaxprocess(frm,post_url)
		} 
		else
			frmresult.addClass('error').html('<p>Vul svp alle velden goed in</p>').fadeIn(800);
		return false;
	});
})



