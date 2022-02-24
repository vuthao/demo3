<?php

/**
 * @Project NUKEVIET 4.x
 * @Author VINADES.,JSC <contact@vinades.vn>
 * @Copyright (C) 2017 VINADES.,JSC. All rights reserved
 * @License GNU/GPL version 2 or any later version
 * @Createdate 04/18/2017 09:47
 */

if (!defined('NV_IS_FILE_ADMIN')) {
    die('Stop!!!');
}

if (!defined('NV_IS_SPADMIN')) {
    nv_redirect_location(NV_BASE_ADMINURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name);
}

$page_title = $lang_module['template'];
$error = "";
$savecat = 0;

$data = array(
    "title" => "",
    'alias' => ""
);

$table_name = $db_config['prefix'] . '_' . $module_data . '_template';
$data['id'] = $nv_Request->get_int('id', 'post,get', 0);
$savecat = $nv_Request->get_int('savecat', 'post', 0);

// Thay đổi hoạt động
if ($nv_Request->isset_request('change_active', 'post')) {
    $id = $nv_Request->get_int('id', 'post', 0);

    $id = $db->query('SELECT id FROM ' . $table_name . ' WHERE id=' . $id)->fetchColumn();
    if (empty($id)) {
        die('NO_' . $id);
    }

    $new_status = $nv_Request->get_bool('new_status', 'post');
    $new_status = ( int )$new_status;

    $sql = 'UPDATE ' . $table_name . ' SET status=' . $new_status . ' WHERE id=' . $id;
    $db->query($sql);

    $nv_Cache->delMod($module_name);
    nv_htmlOutput('OK_' . $pid);
}

// Thay đổi thứ tự
if ($nv_Request->isset_request('changeweight', 'post')) {
    $id = $nv_Request->get_int('id', 'post', 0);

    $id = $db->query('SELECT id FROM ' . $table_name . ' WHERE id=' . $id)->fetchColumn();
    if (empty($id)) {
        die('NO_' . $id);
    }

    $new_weight = $nv_Request->get_int('new_weight', 'post', 0);
    if (empty($new_weight)) {
        die('NO_' . $mod);
    }

    $sql = 'SELECT id FROM ' . $table_name . ' WHERE id!=' . $id . ' ORDER BY weight ASC';
    $result = $db->query($sql);

    $weight = 0;
    while ($row = $result->fetch()) {
        ++$weight;
        if ($weight == $new_weight) {
            ++$weight;
        }

        $sql = 'UPDATE ' . $table_name . ' SET weight=' . $weight . ' WHERE id=' . $row['id'];
        $db->query($sql);
    }

    $sql = 'UPDATE ' . $table_name . ' SET weight=' . $new_weight . ' WHERE id=' . $id;
    $db->query($sql);

    nv_insert_logs(NV_LANG_DATA, $module_name, 'Change template weight', 'ID: ' . $id, $admin_info['userid']);
    $nv_Cache->delMod($module_name);

    nv_htmlOutput('OK_' . $pid);
}

if (!empty($savecat)) {
    $preg_replace = array(
        'pattern' => '/[^a-zA-Z0-9\_]/',
        'replacement' => '_'
    );

    $data['title'] = nv_substr($nv_Request->get_title('title', 'post', ''), 0, 50);
    $data['alias'] = strtolower(change_alias($data['title']));

    $count = $db->query('SELECT * FROM ' . $db_config['prefix'] . '_' . $module_data . '_template WHERE alias=' . $db->quote($data['alias']) . ' AND id!=' . $data['id'])->fetchColumn();
    if ($count > 0) {
        $_tem_id = $db->query('SELECT MAX(id) FROM ' . $db_config['prefix'] . '_' . $module_data . '_template WHERE alias=' . $db->quote($data['alias']))->fetchColumn();
        $data['alias'] = $data['alias'] . '-' . $_tem_id;
    }

    if (empty($data['title'])) {
        $error = $lang_module['template_error_name'];
    } else {
        if ($data['id'] == 0) {
            $listfield = "";
            $listvalue = "";

            $weight = $db->query("SELECT MAX(weight) FROM " . $table_name)->fetchColumn();
            $weight = intval($weight) + 1;

            $sql = "INSERT INTO " . $table_name . " (
                status, " . NV_LANG_DATA . "_title, alias, weight
            ) VALUES (
                1, " . $db->quote($data['title']) . ", " . $db->quote($data['alias']) . ", " . $weight . "
            )";
            $templaid = $db->insert_id($sql);
            if ($templaid != 0) {
                $nv_Cache->delMod($module_name);
                nv_redirect_location(NV_BASE_ADMINURL . "index.php?" . NV_NAME_VARIABLE . "=" . $module_name . "&" . NV_OP_VARIABLE . "=" . $op);
            } else {
                $error = $lang_module['errorsave'];
            }
        } else {
            $stmt = $db->prepare("UPDATE " . $table_name . " SET " . NV_LANG_DATA . "_title= :title WHERE id =" . $data['id']);
            $stmt->bindParam(':title', $data['title'], PDO::PARAM_STR);
            if ($stmt->execute()) {
                $error = $lang_module['saveok'];

                $nv_Cache->delMod($module_name);
                nv_redirect_location(NV_BASE_ADMINURL . "index.php?" . NV_NAME_VARIABLE . "=" . $module_name . "&" . NV_OP_VARIABLE . "=" . $op);
            } else {
                $error = $lang_module['errorsave'];
            }
        }
    }
} else {
    if ($data['id'] > 0) {
        $data_old = $db->query("SELECT * FROM " . $table_name . " WHERE id=" . $data['id'])->fetch();
        $data = array(
            "id" => $data_old['id'],
            "title" => $data_old[NV_LANG_DATA . '_title'],
            "alias" => $data_old['alias']
        );
    }
}

$xtpl = new XTemplate("template.tpl", NV_ROOTDIR . "/themes/" . $global_config['module_theme'] . "/modules/" . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('DATA', $data);
$xtpl->assign('caption', empty($data['id']) ? $lang_module['template_add'] : $lang_module['template_edit']);
$xtpl->assign('TEM_ADD', NV_BASE_ADMINURL . "index.php?" . NV_NAME_VARIABLE . "=" . $module_name . "&" . NV_OP_VARIABLE . "=template#add");

$result = $db->query("SELECT id, " . NV_LANG_DATA . "_title, alias, status, weight FROM " . $table_name . " ORDER BY weight ASC");
$num = $result->rowCount();

while (list($id, $title, $alias, $status, $weight) = $result->fetch(3)) {
    $xtpl->assign('title', $title);
    $xtpl->assign('alias', $alias);
    $xtpl->assign('active', $status ? 'checked="checked"' : '');
    $xtpl->assign('FIELD_TAB', NV_BASE_ADMINURL . "index.php?" . NV_NAME_VARIABLE . "=" . $module_name . "&" . NV_OP_VARIABLE . "=field_tab&template=". $id);
    $xtpl->assign('link_edit', NV_BASE_ADMINURL . "index.php?" . NV_NAME_VARIABLE . "=" . $module_name . "&" . NV_OP_VARIABLE . "=" . $op . "&id=" . $id);
    $xtpl->assign('link_del', NV_BASE_ADMINURL . "index.php?" . NV_NAME_VARIABLE . "=" . $module_name . "&" . NV_OP_VARIABLE . "=detemplate&id=" . $id);
    $xtpl->assign('id', $id);

    for ($i = 1; $i <= $num; ++$i) {
        $xtpl->assign('WEIGHT', [
            'w' => $i,
            'selected' => ($i == $weight) ? ' selected="selected"' : ''
        ]);

        $xtpl->parse('main.data.row.weight');
    }

    $xtpl->parse('main.data.row');
}

$xtpl->assign('FIELD_ADD', NV_BASE_ADMINURL . "index.php?" . NV_NAME_VARIABLE . "=" . $module_name . "&" . NV_OP_VARIABLE . "=fields#ffields");
$xtpl->assign('URL_DEL', NV_BASE_ADMINURL . "index.php?" . NV_NAME_VARIABLE . "=" . $module_name . "&" . NV_OP_VARIABLE . "=detemplate");
$xtpl->assign('URL_DEL_BACK', NV_BASE_ADMINURL . "index.php?" . NV_NAME_VARIABLE . "=" . $module_name . "&" . NV_OP_VARIABLE . "=" . $op);

if ($num > 0) {
    $xtpl->parse('main.data');
}

if ($error != '') {
    $xtpl->assign('error', $error);
    $xtpl->parse('main.error');
}

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_admin_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
