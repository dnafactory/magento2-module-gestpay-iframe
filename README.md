###magento2-module-gestpay-iframe

Integrazione iFrame BancaSellaPRO

####Aggiungere iFrame nelle email

Aggiungere questo handle per renderizzare il link al pagamento:
<pre>
{{layout handle="sales_email_order_iframe" order_id=$order_id}}
</pre>
