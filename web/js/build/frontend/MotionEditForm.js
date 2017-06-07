define(["require","exports","../shared/DraftSavingEngine","../shared/AntragsgruenEditor"],function(t,n,e,a){"use strict";Object.defineProperty(n,"__esModule",{value:!0});var i=function(){function t(n){this.$form=n,this.hasChanged=!1,$(".input-group.date").datetimepicker({locale:$("html").attr("lang"),format:"L"}),$(".wysiwyg-textarea").each(this.initWysiwyg.bind(this)),$(".form-group.plain-text").each(this.initPlainTextFormGroup.bind(this));var a=$("#draftHint"),i=a.data("motion-type"),o=a.data("motion-id");new e.DraftSavingEngine(n,a,"motion_"+i+"_"+o),n.on("submit",function(){$(window).off("beforeunload",t.onLeavePage)})}return t.onLeavePage=function(){return __t("std","leave_changed_page")},t.prototype.initWysiwyg=function(n,e){var i=this,o=$(e).find(".texteditor"),r=new a.AntragsgruenEditor(o.attr("id"));o.parents("form").submit(function(){o.parent().find("textarea").val(r.getEditor().getData())}),r.getEditor().on("change",function(){i.hasChanged||(i.hasChanged=!0,$("body").hasClass("testing")||$(window).on("beforeunload",t.onLeavePage))})},t.prototype.initPlainTextFormGroup=function(t,n){var e=$(n),a=e.find("input.form-control");if(0!=e.data("max-len")){var i=e.data("max-len"),o=!1,r=e.find(".maxLenTooLong"),d=e.parents("form").first().find("button[type=submit]"),s=e.find(".maxLenHint .counter");i<0&&(o=!0,i*=-1),a.on("keyup change",function(){var t=a.val().length;s.text(t),t>i?(r.removeClass("hidden"),o||d.prop("disabled",!0)):(r.addClass("hidden"),o||d.prop("disabled",!1))}).trigger("change")}},t}();n.MotionEditForm=i});
//# sourceMappingURL=MotionEditForm.js.map
