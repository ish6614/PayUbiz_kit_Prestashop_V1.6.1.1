<div class='payubizPayNow'>
<form id='payubizPayNow' action="{$data.payubiz_url}" method="post">
    <p class="payment_module"> 
    {foreach $data.info as $k=>$v}
        <input type="hidden" name="{$k}" value="{$v}" />
    {/foreach}  
     <a href='#' onclick='document.getElementById("payubizPayNow").submit();return false;'>{$data.payubiz_paynow_text}
      {if $data.payubiz_paynow_logo=='on'} <img align='{$data.payubiz_paynow_align}' alt='Pay Now With PayUbiz' title='Pay Now With PayUbiz' src="{$base_dir}modules/payubiz/logo.png" style="padding-right:20px;">{/if}</a>
       <noscript><input type="image" src="{$base_dir}modules/payubiz/logo.png" ></noscript>
    </p> 
</form>
</div>
<div class="clear"></div>
