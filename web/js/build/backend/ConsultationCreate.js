define(["require","exports","../shared/SiteCreateWizard"],function(e,t){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var n=function(){function e(){$(".settingsType").find("input[type=radio]").change(e.settingsTypeChanged).change();var t=$(".consultationEditForm");t.find(".delbox button").click(function(e){e.preventDefault();var n=$(this);bootbox.confirm(__t("admin","consDeleteConfirm"),function(e){if(e){n.data("id");var i=$('<input type="hidden">').attr("name",n.attr("name")).attr("value",n.attr("value"));t.append(i),t.submit()}})}),new SiteCreateWizard($(".siteCreate"))}return e.settingsTypeChanged=function(){$("#settingsTypeWizard").prop("checked")?($(".settingsTypeWizard").removeClass("hidden"),$(".settingsTypeTemplate").addClass("hidden")):($(".settingsTypeWizard").addClass("hidden"),$(".settingsTypeTemplate").removeClass("hidden"))},e}();t.ConsultationCreate=n,new n});
//# sourceMappingURL=ConsultationCreate.js.map
