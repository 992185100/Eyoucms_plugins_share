$(function () {
    $("#click-clicap-create").click(function(){
        $('#clicaptcha-submit-info').clicaptcha({
            src: __root_dir__ + "/index.php?m=plugins&c=Clicap&a=create",
            callback: function(e){
                $("#click-clicap-create").html('<i class="icon iconfont" >&#xe68b;</i>&nbsp;验证成功！');
                $("#click-clicap-create").removeClass("clicap-check-error");
                $("#click-clicap-create").addClass("clicap-check-right");
                return;
            }
        });
        $("#click-clicap-create").html('<i class="icon iconfont" >&#xe633;</i>&nbsp;验证失败，点击重新验证！');
        $("#click-clicap-create").removeClass("clicap-check-right");
        $("#click-clicap-create").addClass("clicap-check-error");
    });
})