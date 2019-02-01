<?php
$_['name']           = 'Product';
$_['id']             = 'product_id';
$_['table_name']     = 'product_description';
$_['multi_language'] = 1;
$_['table_fields']   = array('description');
$_['events']         = array(
    'admin/model/catalog/product/editProduct/before' => 'extension/d_editor_history_module/product/model_editProduct_before',
    'admin/view/catalog/product_form/after' => 'extension/d_editor_history_module/product/view_product_form_after',
    'admin/view/extension/d_visual_designer/frontend_editor/after' => 'extension/d_editor_history_module/product/view_vd_frontend_after',
    'catalog/model/extension/module/d_visual_designer/editProduct/before' => 'extension/d_editor_history_module/product/model_editProduct_before',
);