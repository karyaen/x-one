function showHideQueryBox(e,t){0==e.find("select").val()?t.addClass("closed"):t.removeClass("closed")}!function($){$(document).ready(function(){if($("body").is(".post-new-php")&&$(".create-view").click(function(){$(this).parents(".view-create-new-notice-wrapper").fadeOut()}),$("body").is(".post-php")){$("#masonry, #slider, #carousel, #grid").hide();var e=$("#field-current-layouts .field-type-raw").text();$("#"+e).show(),setTimeout(function(){$("#message, .update-nag").fadeOut()},3500);var t=$("#titlediv").find("input").val(),o="IntroTip-"+t,a=localStorage.getItem(o);1==a&&$(".start-message").hide(),$(".close-start-message").click(function(){$(".start-message").fadeOut("slow"),localStorage.setItem(o,1)});var d=$("#wpbody-content"),i=$("#publish");i.clone(!0).addClass("cloned-save top").prependTo(d),i.clone(!0).addClass("cloned-save bottom").appendTo(d),i.hide(),$(".cloned-save").click(function(){i.trigger("click")});var s=$("#to-move").find("#field-shortcode-info .field-type-raw").text(),n='<div id="shortcode-preview" class="toolbar-wrap">'+s+"</div>";$("body.post-php #views-toolbar").prepend(n);var r=$("#to-move").find(".field-type-switch"),c=$("#advanced-query-params");c.find(".handlediv").hide();var l=r.clone(!0).prependTo(c).addClass("query-switch");c.find(".hndle").off(),showHideQueryBox(l,c),l.change(function(){r.find("select").val($(this).val()).trigger("change"),showHideQueryBox($(this),c)}),$("#to-move").hide()}})}(jQuery);