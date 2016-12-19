define(["require","exports","./AntragsgruenEditor"],function(t,e,a){"use strict";var i=function(){function t(){var t=this;this.$paragraphs=$(".wysiwyg-textarea.single-paragraph"),this.$paragraphs.click(function(e){t.startEditing(e.delegateTarget)}),this.$paragraphs.find(".modifiedActions .revert").click(function(e){e.preventDefault(),e.stopPropagation(),t.revertChanges(e.delegateTarget)}),this.setModifyable(),$(".wysiwyg-textarea").filter(":not(.single-paragraph)").each(function(e,a){t.handleRegularTextField(a)}),$(".texteditorBox").each(function(t,e){var a=$(e),i=a.data("section-id"),n=a.data("changed-para-no");n>-1&&$("#section_holder_"+i+"_"+n).click()})}return t.prototype.handleRegularTextField=function(t){var e=$(t),i=e.find(".texteditor");if(!e.hasClass("hidden")){var n=new a.AntragsgruenEditor(i.attr("id")),r=n.getEditor();i.parents("form").submit(function(){i.parent().find("textarea.raw").val(r.getData()),"undefined"!=typeof r.plugins.lite&&(r.plugins.lite.findPlugin(r).acceptAll(),i.parent().find("textarea.consolidated").val(r.getData()))})}},t.prototype.revertChanges=function(t){var e=$(t),a=e.parents(".wysiwyg-textarea"),i=a.find(".texteditor"),n=i.attr("id");$("#"+n).attr("contenteditable","false"),i.html(a.data("original")),a.removeClass("modified"),this.setModifyable()},t.prototype.startEditing=function(t){var e=$(t);if(e.hasClass("modifyable")){e.addClass("modified"),this.setModifyable();var i,n=e.find(".texteditor");if("undefined"!=typeof CKEDITOR.instances[n.attr("id")])i=CKEDITOR.instances[n.attr("id")];else{var r=new a.AntragsgruenEditor(n.attr("id"));i=r.getEditor()}n.attr("contenteditable","true"),n.parents("form").submit(function(){n.parent().find("textarea.raw").val(i.getData()),"undefined"!=typeof i.plugins.lite&&(i.plugins.lite.findPlugin(i).acceptAll(),n.parent().find("textarea.consolidated").val(i.getData()))}),n.focus()}},t.prototype.setModifyable=function(){var t=this.$paragraphs.filter(".modified");0==t.length?this.$paragraphs.addClass("modifyable"):(this.$paragraphs.removeClass("modifyable"),$("input[name=modifiedParagraphNo]").val(t.data("paragraph-no")),$("input[name=modifiedSectionId]").val(t.parents(".texteditorBox").data("section-id")))},t}();e.AmendmentEditSinglePara=i});
//# sourceMappingURL=AmendmentEditSinglePara.js.map
