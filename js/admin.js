jQuery(function($){
    // Test Connection
    $('.isarud-test-btn').on('click',function(){
        var btn=$(this),mp=btn.data('marketplace'),result=$('.isarud-test-result[data-marketplace="'+mp+'"]');
        btn.prop('disabled',true);
        result.html('<span class="isarud-spinner"></span> Test ediliyor...');
        $.post(isarud.ajax,{action:'isarud_test_connection',nonce:isarud.nonce,marketplace:mp},function(r){
            btn.prop('disabled',false);
            if(r.success) result.html('<span class="success">✓ '+r.data.message+'</span>');
            else result.html('<span class="error">✗ '+(r.data&&r.data.message?r.data.message:'Bağlantı başarısız')+'</span>');
        }).fail(function(){btn.prop('disabled',false);result.html('<span class="error">✗ İstek başarısız</span>');});
    });

    // Sync Product
    $(document).on('click','.isarud-sync-btn',function(){
        var btn=$(this),pid=btn.data('product'),mp=btn.data('marketplace');
        btn.prop('disabled',true).text('Senkronize ediliyor...');
        $.post(isarud.ajax,{action:'isarud_sync_product',nonce:isarud.nonce,product_id:pid,marketplace:mp},function(r){
            btn.prop('disabled',false);
            if(r.success) btn.text('✓ Tamam').css('color','#00a32a');
            else{btn.text('✗ Hata').css('color','#d63638');alert(r.data);}
            setTimeout(function(){btn.text('Sync').css('color','');},3000);
        });
    });

    // Screen Order
    $(document).on('click','.isarud-screen-order',function(){
        var btn=$(this),oid=btn.data('order');
        btn.prop('disabled',true).text('Taranıyor...');
        $.post(isarud.ajax,{action:'isarud_screen_order',nonce:isarud.nonce,order_id:oid},function(r){
            btn.prop('disabled',false);
            if(r.success) location.reload();
            else{btn.text('Hata');alert(r.data);}
        });
    });

    // Bulk Sync
    $('#isarud-bulk-start').on('click',function(){
        var btn=$(this), mp=$('#isarud-bulk-mp').val(), page=0;
        btn.prop('disabled',true);
        $('#isarud-bulk-progress').show();
        var totalOk=0, totalErr=0;

        function syncPage(p){
            $.post(isarud.ajax,{action:'isarud_bulk_sync',nonce:isarud.nonce,marketplace:mp,page:p},function(r){
                if(!r.success){$('#isarud-bulk-status').text('Hata: '+r.data);btn.prop('disabled',false);return;}
                totalOk+=r.data.ok; totalErr+=r.data.err;
                var processed=(p+1)*10;
                var pct=r.data.total>0?Math.min(100,Math.round(processed/r.data.total*100)):100;
                $('#isarud-bulk-bar').css('width',pct+'%');
                $('#isarud-bulk-status').text(totalOk+' başarılı, '+totalErr+' hata — '+pct+'%');
                if(!r.data.done) syncPage(p+1);
                else{
                    btn.prop('disabled',false);
                    $('#isarud-bulk-status').html('<strong>Tamamlandı!</strong> '+totalOk+' başarılı, '+totalErr+' hata');
                }
            });
        }
        syncPage(0);
    });
});
