$(document).ready(function () {
if($('.hidden-data').length) hidden_data=$('.hidden-data').val().split(" ");
else hidden_data=[];
$('.opmap').live('click', function(){
	num_child=$('.opmap').index(this);
	$(".myiframe").attr('src','/index.php/yandexmap-road-coordinate?data='+hidden_data[num_child])
	$('.divmap_total').css("display", "block");
});	
	$('.date1').will_pickdate({
						format: 'd.m.Y H:i', 
						 inputOutputFormat: 'd.m.Y H:i',
						 days: ['Вс','Пн', 'Вт', 'Ср', 'Чт','Пт', 'Сб'],
						 months: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
						 timePicker: true,
						 militaryTime: true,
						 yearsPerPage:3,
						 allowEmpty:true,
						 startDay: 1
						  });	

		$('.city_start, .city_stop').live('keyup input', function () {
		flag = false;
		
		th = $(this)
		length = $(this).val().length;
		if (length == 0) {
			$(".strcity").remove();
		} else {
			if (length % 3 == 0) {
				$(".strcity").remove();
				$.ajax({
					type: "POST",
					url: "index.php?option=com_scriptquery&task=GetCity&no_html=1",
					data: {
						str_city: $(this).val()
					},
					dataType: "text",
					async: true,
					success: function (data) {
						if (!flag) {
							flag = true;
							if (data.length > 1) {
								data = data.substring(1);
								th.parent().append("<div class='strcity'>" + data + "</div>");
							} else {}
						}
					}
				});
			}
		}
	});
	
	$('.strcity div').live('click', function () {
		title=$(this).text();
		$(this).parent().parent().find(".city_start, .city_stop").val(title);
		$(".strcity").remove();		
	});
	$(document).live('click', function (e) { // событие клика по веб-документу
		var div = $(".strcity"); // тут указываем ID элемента
		if (!div.is(e.target) // если клик был не по нашему блоку
		    && div.has(e.target).length === 0) { // и не по его дочерним элементам
			div.remove();
		}
	});
	
$('.addfavouritesroad').click(function () {
	el=this;
	id_total=el.getAttribute('id');
	masid=id_total.split(" ");
	userid=masid[0];
	id_company=masid[1];
	idroad=masid[2];
	uuu=document.getElementById("uuu").value;
	fff=document.getElementById("fff").value;
	if(uuu==fff) typeuser="boss";
	else typeuser="employ";
	
	$.ajax({
					  type: "POST",
					  url: "index.php?option=com_mapquery_2&task=AddProposal_list&no_html=1",
					  data: {map_type:'cargo', userid:userid, id_company:id_company, idroad:idroad, typeuser:typeuser},
					  dataType: "text",
					  async:false,
					  success: function(data)
					  {
					   substr= data.substring(1,7);
						if( substr.length>0 && substr=="<table" )
						{
							$(".listitemtext").append(data);
							$(".greybackground").fadeIn(20);
						}
						 else if(data.length>0){
							 $(".listitemtext").append(data);
							$(".greybackground").fadeIn(20);
						  }
						  else{}
					  	
					  }
					  });		
	});
	
	$('.no_add').live('mouseenter', function () {
		var url =  location.protocol + "//" + location.host;
		$(this).find(".div_add").remove();
		$(this).find("td").last().append("<div class='div_add'><img src='"+url+"/images/handgrenn.png' title='Сделать предложение'/></div>");	
		$(this).find(".div_add").animate({right: '0%'}, 250);
    }).live('mouseleave', function () {
		$(this).find(".div_add").animate( {right: '-100%'}, 250, function(){$(this).remove(); });			
	});
	
	$('.is_add').live('mouseenter', function () {
		var url =  location.protocol + "//" + location.host;
		$(this).find(".del_add").remove();
		$(this).find("td").last().append("<div class='del_add'><img src='"+url+"/images/dell_item.png' title='Отменить предложение'/></div>");	
		$(this).find(".del_add").animate({right: '0%'}, 250);
    }).live('mouseleave', function () {
		$(this).find(".del_add").animate( {right: '-100%'}, 250, function(){$(this).remove(); });			
	});	
	//для планшетов тоже самое что и выше только по клику
		$('.is_add').live('click', function () {
		var url =  location.protocol + "//" + location.host;
		$(this).find(".del_add").remove();
		$(this).find("td").last().append("<div class='del_add'><img src='"+url+"/images/dell_item.png' title='Отменить предложение'/></div>");	
		$(this).find(".del_add").animate({right: '0%'}, 250);
    });
	
	$('.no_add').live('click', function () {
		var url =  location.protocol + "//" + location.host;
		data_contractor=$(this).closest('table').attr('data-contractor');
		tr=$(this).closest('tr');
		mas_contractor= data_contractor.split(' ');
		id_user_contractor = mas_contractor[0];
		id_company_contractor = mas_contractor[1];
		idroad_contractor = mas_contractor[2];
		
		data_customer=$(this).attr('data-customer');
		mas_customer = data_customer.split(' ');
		idfirm_customer = mas_customer[0];
		idputh_customer = mas_customer[1];
		id_customer=mas_customer[2];
		
		$.ajax({
					  type: "POST",
					  url: "index.php?option=com_mapquery_2&task=AddProposal&no_html=1",
					  data: {map_type:'cargo', id_user_contractor:id_user_contractor, id_company_contractor:id_company_contractor, idroad_contractor:idroad_contractor, id_customer:id_customer,idfirm_customer:idfirm_customer, idputh_customer:idputh_customer},
					  dataType: "text",
					  async:false,
					  success: function(data)
					  {
						if(data.length<15)//проверяем что нет сообщений об ошибках, сюда возвращается id
						{
							tr.attr({'class':'is_add'});
							tr.attr({'data-dell':data.substring(1)}); //записываем в атрибут id по которому можно удалить испольнителя
							tr.find(".div_add").remove();
							tr.find("td").last().append("<div class='div_isadd' style='display:none;'><img src='"+url+"/images/galkas.png' title='Предложение сделано'/></div>");
							tr.find(".div_isadd").fadeIn(200);
							tr.find("td").last().append("<div class='del_add'><img src='"+url+"/images/dell_item.png' title='Отменить предложение'/></div>");	
							tr.find(".del_add").animate({right: '0%'}, 250);
							
						}
					  }
		});
		
	});
	
		$('.del_add img').live('click', function (evt) {
			evt.stopPropagation();//останавлием действие события на дочерние элементы, чтобы не сработал клик на нажатие на всю чтроку и не сработала функция предназначенная для клика для планшетов
			var url =  location.protocol + "//" + location.host;
			tr=$(this).closest('tr');
			id=tr.attr('data-dell');
			$.ajax({
					  type: "POST",
					  url: "index.php?option=com_mapquery_2&task=DellProposal&no_html=1",
					  data: {map_type:'cargo', id:id},
					  dataType: "text",
					  async:false,
					  success: function(data)
					  {
						if(data.length<2)//проверяем что нет сообщений об ошибках
						{
							tr.find(".del_add").remove();
							tr.find(".div_isadd").remove();
							tr.attr({'class':'no_add'});
							tr.find("td").last().append("<div class='div_add'><img src='"+url+"/images/handgrenn.png' title='Сделать предложение'/></div>");	
							tr.find(".div_add").animate({right: '0%'}, 250);
						}
					  }
		});
		});
	
	$('.close_list').click(function () {
		$(".greybackground").fadeOut(20);
		$(".listitemtext").empty();		
		});

	$('.addfavourites, .delfavorites').click(function () { 
		el=this;
		idfirm=document.getElementById("fff").value;
		clasname_total=el.getAttribute('class');
		favoritefirm=clasname_total.split(" ");
		clasname=favoritefirm[0];
		favoritefirm=favoritefirm[1];		
		
		if(clasname=="addfavourites")
			{
				task="AddFavorites";
				clasname_change="delfavorites "+favoritefirm;
				title='Удалить грузоперевозчика из избранного';
			}
		else{
			task="DelFavorites";
			clasname_change="addfavourites "+favoritefirm;
			title='Добавить грузоперевозчика в избранное';
		}

		$.ajax({
					  type: "POST",
					  url: "index.php?option=com_mapquery_2&task="+task+"&no_html=1",
					  data: {idfirm:idfirm, favoritefirm:favoritefirm},
					  dataType: "text",
					  async:false,
					  success: function(data)
					  {
					     if(data.length==1)
						 {
							 text1 = el.getAttribute('data-title1');
							 text2 = el.getAttribute('data-title2');
							 
							 var elements = document.getElementsByClassName(favoritefirm);
							 for (i = 0; i < elements.length; i++) {
								 elements[i].setAttribute("data-title1", text2);
								 elements[i].setAttribute("data-title2", text1);
								 elements[i].setAttribute("title", title);
							     elements[i].setAttribute("class", clasname_change);
							 }							 
							 show_tip(text1);
						 }
					  }
			 });
	});
	
	$('.strelkaup').click(function () {
		$('.tipblock2').fadeOut(200, function(){
		$('.tipblock').append("<div class='text2'>Фильтр<div class='strelkadown'></div></div>");
		$('.text2').fadeIn(200);
			
		});
		$.ajax({
					  type: "POST",
					  url: "index.php?option=com_mapquery_2&task=FIlterShowHide&no_html=1",
					  data: {show:"hide"},
					  dataType: "text",
					  async:true
			 });
		});
	$(".tipblock").on("click", ".strelkadown", function() {
		$('.text2').fadeOut(200, function(){
		$('.text2').remove();
		$('.tipblock2').fadeIn(200);
		});
		$.ajax({
					  type: "POST",
					  url: "index.php?option=com_mapquery_2&task=FIlterShowHide&no_html=1",
					  data: {show:"show"},
					  dataType: "text",
					  async:true
			 });
		});
	
	
	$('.block2').on('change', "input", function(){
        $(this).parent().toggleClass('activ');
    });

});