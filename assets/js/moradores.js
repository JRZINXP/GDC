$(document).ready(function(){

    $("#formNovoMorador").submit(function(e){
        e.preventDefault();

        $.ajax({
            url: "../../controller/Sindico/api_morador.php",
            type: "POST",
            data: $(this).serialize(),

            success: function(res){

                if(res.status === "ok"){
                    alert(res.msg);

                    $("#formNovoMorador")[0].reset();
                    fecharModalNovo();

                    // recarregar lista
                    location.reload();

                }else{
                    alert(res.msg);
                }
            },

            error: function(){
                alert("Erro AJAX");
            }

        });
    });

});
