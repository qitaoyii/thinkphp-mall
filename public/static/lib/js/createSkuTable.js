var alreadySetSkuVals = {};//已经设置的SKU值数据
$(function(){
	$(document).on("change",'.sku_value',function(){
		getAlreadySetSkuVals();//获取已经设置的SKU值
		// console.log(alreadySetSkuVals);
		var i = 0;
		$('.sku_value').each(function () {
			if($(this).is(":checked")){
				i++;
			}
		});

		// 虚拟商品获取
		var is_object = $("#is-object").val();
		var is_hide = 'style=""';
		if (is_object == 0) {
            is_hide = 'style="display:none"';
		}
		var b = true;
		var skuTypeArr =  [];//存放SKU类型的数组
		var totalRow = 1;//总行数
		//获取元素类型
		$(".SKU_TYPE").each(function(){
			//SKU类型节点
			// console.log($(this), '$(this)');
			var skuTypeNode = $(this).children("li");
			var skuTypeObj = {};//sku类型对象
			//SKU属性类型标题
			skuTypeObj.skuTypeTitle = $(skuTypeNode).attr("sku-type-name");
			//SKU属性类型主键
			var propid = $(skuTypeNode).attr("propid");
			skuTypeObj.skuTypeKey = propid;
			//是否是必选SKU 0：不是；1：是；
			var is_required = $(skuTypeNode).attr("is_required");
			skuValueArr = [];//存放SKU值得数组
			//SKU相对应的节点
			var skuValNode = $(this).next();
			//获取SKU值
			var skuValCheckBoxs = $(skuValNode).find("input[type='checkbox'][class*='sku_value']");
			var checkedNodeLen = 0 ;//选中的SKU节点的个数
			$(skuValCheckBoxs).each(function(){
				if($(this).is(":checked")){
					var skuValObj = {};//SKU值对象
					skuValObj.skuValueTitle = $(this).val();//SKU值名称
					skuValObj.skuValueId = $(this).attr("propvalid");//SKU值主键
					skuValObj.skuPropId = $(this).attr("propid");//SKU类型主键
					skuValueArr.push(skuValObj);
					checkedNodeLen ++ ;
				}
			});


			if(is_required && "1" == is_required){//必选sku
				if(checkedNodeLen <= 0){//有必选的SKU仍然没有选中
					b = false;
					return false;//直接返回
				}
			}
			if(skuValueArr && skuValueArr.length > 0){
				totalRow = totalRow * skuValueArr.length;
				skuTypeObj.skuValues = skuValueArr;//sku值数组
				skuTypeObj.skuValueLen = skuValueArr.length;//sku值长度
				skuTypeArr.push(skuTypeObj);//保存进数组中
			}
		});
		var SKUTableDom = "";//sku表格数据
		//开始创建行
		if(b){//必选的SKU属性已经都选中了

			SKUTableDom += "<table class='skuTable'><tr>";
			//创建表头
			for(var t = 0 ; t < skuTypeArr.length ; t ++){
				SKUTableDom += '<th>'+skuTypeArr[t].skuTypeTitle+'</th>';
			}
			SKUTableDom +=  '<th> 生产日期 </th>' +
				'<th> 生产量 </th>' +
				'<th> 条码编号 </th>' +
				'<th '+is_hide+'> 包装毛重（kg）</th>' +
				'<th> 认证版谷 </th>' +
				'<th> sku预览图 </th>';
			SKUTableDom += "</tr>";
			//循环处理表体
			for(var i = 0 ; i < totalRow ; i ++){//总共需要创建多少行
				var currRowDoms = "";
				var rowCount = 1;//记录行数
				var propvalidArr = [];//记录SKU值主键
				var propIdArr = [];//属性类型主键
				var propvalnameArr = [];//记录SKU值标题
				var propNameArr = [];//属性类型标题
				for(var j = 0 ; j < skuTypeArr.length ; j ++){//sku列
					var skuValues = skuTypeArr[j].skuValues;//SKU值数组
					var skuValueLen = skuValues.length;//sku值长度
					rowCount = (rowCount * skuValueLen);//目前的生成的总行数
					var anInterBankNum = (totalRow / rowCount);//跨行数
					var point = ((i / anInterBankNum) % skuValueLen);
					propNameArr.push(skuTypeArr[j].skuTypeTitle);
					if(0  == (i % anInterBankNum)){//需要创建td
						currRowDoms += '<td rowspan='+anInterBankNum+'>'+skuValues[point].skuValueTitle+'</td>';
						propvalidArr.push(skuValues[point].skuValueId);
						propIdArr.push(skuValues[point].skuPropId);
						propvalnameArr.push(skuValues[point].skuValueTitle);
					}else{
						//当前单元格为跨行
						propvalidArr.push(skuValues[parseInt(point)].skuValueId);    // id
						propIdArr.push(skuValues[parseInt(point)].skuPropId);
						propvalnameArr.push(skuValues[parseInt(point)].skuValueTitle);  // 名称
					}
				}

				// var propvalids = propvalidArr.toString(); // 老版
				var propvalids = propvalnameArr.toString().split(',').join(sign);// 新版
				var alreadySetSkuProductionDate = "";//已经设置的SKU  生产日期
				var alreadySetSkuProductionCount = "";//已经设置的SKU 生产量
				var alreadySetSkuQrcodeNumber = "";//已经设置的SKU 条码编号

				var alreadySetSkuWeight = "";//已经设置的SKU毛重
				var alreadySetSkuImg = "";//已经设置的SKU预览图
				var alreadySetSkuId = "";//已经设置的SKU id

				var alreadySetSkuWorksId = "";//已经设置的SKU 密码图id
				var alreadySetSkuWorksImg = "";//已经设置的SKU 密码图 图片
				var alreadySetSkuWorksName = "";//已经设置的SKU 密码图 图片名称

				if (!propvalids) {
					propvalids = '@#@';
				}
				propvalids = propvalids.replace(';',sign);
				//赋值
				if(alreadySetSkuVals){
					var currGroupSkuVal = alreadySetSkuVals[propvalids];//当前这组SKU值
					if(currGroupSkuVal){
						alreadySetSkuProductionDate = currGroupSkuVal.skuProductionDate;
						alreadySetSkuProductionCount = currGroupSkuVal.skuProductionCount;
						alreadySetSkuQrcodeNumber = currGroupSkuVal.skuQrcodeNumber;
						alreadySetSkuWeight = currGroupSkuVal.skuWeight;
						alreadySetSkuImg = currGroupSkuVal.skuImg;
						alreadySetSkuId = currGroupSkuVal.skuId;
						alreadySetSkuWorksId = currGroupSkuVal.skuWorksId;
						alreadySetSkuWorksImg = currGroupSkuVal.skuWorksImg;
						alreadySetSkuWorksName = currGroupSkuVal.skuWorksName;
					}
					else {
						alreadySetSkuId = '';
						alreadySetSkuImg = "/static/imgs/add.png";
						alreadySetSkuWorksImg = "/static/imgs/add.png";
					}
				}
				var skuProductionDate = $(".skuProductionDate").val() || '';
				var skuProductionCount = $(".skuProductionCount").val() || '';
				var skuQrcodeNumber = $(".skuQrcodeNumber").val() || '';
				var skuWeight = $(".skuWeight").val() || '';
				// var skuWorksId = $(".skuWorksId").val() || '';

				SKUTableDom += '<tr propvalids=\''+propvalids+'\' propids=\''+propIdArr.toString()+'\' propvalnames=\''+propvalnameArr.join(";")+'\'  propnames=\''+propNameArr.join(";")+'\' class="sku_table_tr">'+currRowDoms+'' +
					'<td>' +
					'<input type="hidden" name=\'item['+propvalids+'][id]\' value="'+alreadySetSkuId+'" class="setting_sku_id">' +
					'<input name=\'item['+propvalids+'][sku_production_date]\' type="text" readonly  autocomplete="off" class="setting_sku_production_date dateVal" value="'+alreadySetSkuProductionDate+'" />' +
					'</td>' +
					'<td>' +
					'<input name=\'item['+propvalids+'][sku_production_count]\' type="text" oninput="clearNoNum(this)" class="setting_sku_production_count" value="'+alreadySetSkuProductionCount+'"/>' +
					'</td>' +
					'<td>' +
					'<input type="text" name=\'item['+propvalids+'][sku_qrcode_number]\' oninput="value=value.replace(/[^\\d]/g,\'\')" class="setting_sku_qrcode_number" value="'+alreadySetSkuQrcodeNumber+'"/>' +
					'</td>' +
					'<td '+is_hide+'>' +
					'<input type="text" name=\'item['+propvalids+'][sku_weight]\' oninput="clearNoNum(this)" class="setting_sku_weight" value="'+alreadySetSkuWeight+'"/>' +
					'</td>' +
					'<td class="works-img">' +
					'<img src="'+alreadySetSkuWorksImg+'" width="45" height="45" style="cursor: pointer;" border="0"/>' +
					'<br><span>'+alreadySetSkuWorksName+'</span>'+
					'<input type="hidden" class="setting_sku_works_id" name=\'item['+propvalids+'][sku_works_id]\' value="'+alreadySetSkuWorksId+'">' +
					'</td>' +
					'<td class="pos add-img-btn" skuType="1">' +
					'<img class="setting_sku_img" src="'+alreadySetSkuImg+'" width="45" height="45" style="cursor: pointer;" border="0"/>' +
					'<input type="hidden" name=\'item['+propvalids+'][sku_img]\' value="'+alreadySetSkuImg+'">' +
					'</td>' +
					'</tr>'  ;
				SKUPi = '<div style="margin-top: 32px">' +
					'<span style="margin-left: 60px">批量设置：</span>' +
					'<input type="text" style="width:165px;" placeholder="请输入生产日期" value="'+skuProductionDate+'" class="skuProductionDate input-a dateVals"/>' +
					'<input type="text" style="width:165px;" oninput="value=value.replace(/[^\\d]/g,\'\')" placeholder="请输入生产量" value="'+skuProductionCount+'" class="skuProductionCount input-a"/>' +
					// '<input type="text" placeholder="请输入条码编号" value="'+skuQrcodeNumber+'" class="skuQrcodeNumber input-a"/>' +
					'<input type="text" style="width:165px;" oninput="clearNoNum(this)" placeholder="请输入包装毛重" value="'+skuWeight+'" class="skuWeight input-a"/>' +
					// '<input type="text" placeholder="请输入可获积分" value="'+skuWorksId+'" class="skuWorksId input-a"/>' +
					'&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="changeAll btn btn-fill">确定</button>' +
					'</div>';
			}
			SKUTableDom += "</table>";
		}

		// 新增日期
		$("#skuTable").html(SKUTableDom);

		lay(".dateVal").each(function () {
			laydate.render({
				elem: this,
				type: 'date'
			});
		});

		// 阻止冒泡事件
		$('#skuTable').on('click','.dateVal',function (e) {
			e.stopPropagation();
		});

		var m = 0;
		$(".sku_table_tr").each(function () {
			m++;
		});

		if (m > 1) {
			$('#skupi').html(SKUPi);
		}else{
			$('#skupi').html('');
		}
		lay(".dateVals").each(function () {
			laydate.render({
				elem: this,
				type: 'date'
			});
		});
		// 阻止冒泡事件
		$('#skupi').on('click','.dateVals',function (e) {
			e.stopPropagation();
		});


		// 结算价：仅正数，精确到小数点后两位
		$('.skuProductionDate').keyup(function(){
			$(this)[0].value= $(this)[0].value.match(/\d+(\.\d{0,2})?/) ? $(this)[0].value.match(/\d+(\.\d{0,2})?/)[0] : ''
		});

		// 库存数量：仅正整数
		$('.skuQrcodeNumber').keyup(function(){
			$(this)[0].value=$(this)[0].value.replace(/^(0+)|[^\d]+/g,'')
		});

		$('.changeAll').on('click', function(){
			let mustChange = '';
			var skuProductionDate = $(".skuProductionDate").val();
			var skuProductionCount = $(".skuProductionCount").val();
			var skuQrcodeNumber = $(".skuQrcodeNumber").val();
			var skuWeight = $(".skuWeight").val();
			// var skuWorksId = $(".skuWorksId").val();
			if(!mustChange){
				if (skuProductionDate) {
					$('.setting_sku_production_date').val(skuProductionDate);
				}
				if (skuProductionCount) {
					$('.setting_sku_production_count').val(skuProductionCount);
				}
				if (skuQrcodeNumber) {
					$('.setting_sku_qrcode_number').val(skuQrcodeNumber);
				}
				if (skuWeight) {
					$('.setting_sku_weight').val(skuWeight);
				}
				// $('.setting_sku_works_id').val(skuWorksId);
			}
		})
	});
});

/**
 * 获取已经设置的SKU值
 */
function getAlreadySetSkuVals(){
	alreadySetSkuVals = {};
	//获取设置的SKU属性值
	$("tr[class*='sku_table_tr']").each(function(){
		var skuProductionDate = $(this).find("input[type='text'][class*='setting_sku_production_date']").val();//SKU价格
		var skuProductionCount = $(this).find("input[type='text'][class*='setting_sku_production_count']").val();//SKU价格
		var skuQrcodeNumber = $(this).find("input[type='text'][class*='setting_sku_qrcode_number']").val();//SKU库存
		var skuWeight = $(this).find("input[type='text'][class*='setting_sku_weight']").val();//SKU毛重
		var skuImg = $(this).find(".pos").find('img').attr('src');//SKU预览图
		var skuId = $(this).find("input[type='hidden'][class*='setting_sku_id']").val();//SKUid
		var skuWorksId = $(this).find("input[type='hidden'][class*='setting_sku_works_id']").val();//SKUid
		var skuWorksImg = $(this).find(".works-img").find('img').attr('src');//SKU密码图
		var skuWorksName = $(this).find(".works-img").find('span').html().trim();//SKU密码图名称
		if(skuProductionDate || skuQrcodeNumber || skuWeight || skuImg || skuId){//已经设置了全部或部分值
			var propvalids = $(this).attr("propvalids");//SKU值主键集合
			alreadySetSkuVals[propvalids] = {
				"skuProductionDate" : skuProductionDate,
				"skuProductionCount" : skuProductionCount,
				"skuQrcodeNumber" : skuQrcodeNumber,
				"skuWeight": skuWeight,
				"skuImg": skuImg,
				"skuId" : skuId,
				"skuWorksId" : skuWorksId,
				"skuWorksImg" : skuWorksImg,
				"skuWorksName" : skuWorksName,
			}
		}
	});
}



