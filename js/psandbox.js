
    var editor = ace.edit("editor");
    editor.setTheme("ace/theme/github");
    editor.getSession().setMode("ace/mode/php");
    function invalid(name, type) {
        if (type == 'var' || type == 'global' || type == 'const') {
            return ((/[^a-z0-9_]+/i.test(name)) || (/[^a-z_]+/i.test(name.substring(0, 1))));
        } else if (type == 'func' || type == 'namespace' || type == 'alias' || type == 'class' || type == 'interface' || type == 'trait' || type == 'type') {
            return ((/[^a-z0-9_\\]+/i.test(name)) || (/[^a-z_\\]+/i.test(name.substring(0, 1))));
        } else if (type == 'keyword' || type == 'primitive') {
            return (/[^a-z]+/.test(name));
        } else if (type == 'superglobal' || type == 'magic_const') {
            return ((/[^A-Z0-9_]+/.test(name)) || (/[^A-Z_]+/.test(name.substring(0, 1))));
        }
        return false;
    }
    function name_button(name, type, key) {
        switch (type) {
            case 'func':
                name += '();';
                break;
            case 'var':
                name = '$' + name;
                break;
            case 'global':
                name = 'global $' + name + ';';
                break;
            case 'superglobal':
                name = name.toUpperCase().replace('_', '');
                name = (name == 'GLOBALS' ? '$' + name : '$_' + name) + (key ? "['" + key + "']" : "");
                break;
            case 'const':
                name = name.toUpperCase();
                break;
            case 'magic_const':
                name = '__' + name.toUpperCase().replace('_', '') + '__';
                break;
            case 'alias':
                name = key ? (name + ' as ' + key) : name;
                break;
            case 'namespace':
            case 'type':
                break;
            case 'class':
            case 'interface':
            case 'trait':
                name = key ? (name + ' => ' + key) : name;
                break;
            case 'keyword':
            case 'primitive':
                name = name.toLowerCase();
                break;
        }
        return name;
    }
    function list_keyup(el, select) {
        if (invalid(el.val(), $(select).val())) {
            el.tooltip("enable");
            el.tooltip("open");
        } else {
            el.tooltip("close");
            el.tooltip("disable");
        }
    }
    function make_button(button_name, button_class, type, name) {
        return $("<input/>").attr({"value": button_name, "type": "button", "class": button_class}).data({"type": type, "name": name});
    }
    function sync_code(from_vars) {
        if (from_vars) {
            switch (current_mode) {
                case 'code':
                    editor.setValue(code);
                    break;
                case 'setup_code':
                    editor.setValue(setup_code);
                    break;
                case 'prepend_code':
                    editor.setValue(prepend_code);
                    break;
                case 'append_code':
                    editor.setValue(append_code);
                    break;
            }
            editor.clearSelection();
        } else {
            switch (current_mode) {
                case 'code':
                    code = editor.getValue();
                    console.log(code);
                    break;
                case 'setup_code':
                    setup_code = editor.getValue();
                    break;
                case 'prepend_code':
                    prepend_code = editor.getValue();
                    break;
                case 'append_code':
                    append_code = editor.getValue();
                    break;
            }
        }
    }
    function load_template(response) {
        if (typeof response == "string") {

            response = JSON.parse(response);
        }

        if (!response || typeof response != "object") {
            alert("Could not load template!");
            return;
        }

        code = response.code ? response.code : "";
        setup_code = response.setup_code ? response.setup_code : "";
        prepend_code = response.prepend_code ? response.prepend_code : "";
        append_code = response.append_code ? response.append_code : "";
        sync_code(true);
        var x, type, name, list, button_name, i, button, data;
        if (response.options && response.options.length) {
            for (x in response.options) {
                if (x == 'error_level') {
                    $('#error_level').val(response.options[x])
                } else {
                    $("#" + x).prop('checked', response.options[x] ? true : false);
                }
            }
        }
        $('input.whitelist, input.blacklist, input.func, input.var, input.superglobal, input.const, input.magic_const, input.namespace, input.alias, input.class, input.interface, input.trait').each(function () {
            var el = $(this);
            el.parent().hide();
            el.remove();
        });
        if (response.whitelist) {
            for (type in response.whitelist) {
                list = $("#whitelist_" + type);
                if (response.whitelist[type]) {
                    for (i = 0; i < response.whitelist[type].length; i++) {
                        name = response.whitelist[type][i];
                        button_name = name_button(name, type);
                        list.append(make_button(button_name, "whitelist", type, name)).show();
                    }
                }
            }
        }
        if (response.blacklist) {
            for (type in response.blacklist) {
                list = $("#blacklist_" + type);
                if (response.blacklist[type]) {
                    for (i = 0; i < response.blacklist[type].length; i++) {
                        name = response.blacklist[type][i];
                        button_name = name_button(name, type);
                        list.append(make_button(button_name, "blacklist", type, name)).show();
                    }
                }
            }
        }
        if (response.definitions) {
            for (type in response.definitions) {
                list = $("#define_" + type);
                if (response.definitions[type]) {
                    for (name in response.definitions[type]) {
                        data = response.definitions[type][name];

                        switch (type) {
                            case 'func':
                                button_name = name_button(name, type);
                                button = make_button(button_name, type, type, name);
                                button.data({"args": data.args, "code": data.code, "pass": data.pass, "fullcode": data.fullcode});
                                break;
                            case 'var':
                            case 'const':
                            case 'magic_const':
                                button_name = name_button(name, type);
                                button = make_button(button_name, type, type, name);
                                button.data({"scalar": data.scalar, "value": data.value});
                                break;
                            case 'superglobal':
                                button_name = name_button(name, type, data.key);
                                button = make_button(button_name, type, type, name);
                                button.data({"key": data.key, "scalar": data.scalar, "value": data.value});
                                break;
                            case 'namespace':
                                button_name = name_button(name, type);
                                button = make_button(button_name, type, type, name);
                                break;
                            case 'alias':
                            case 'class':
                            case 'interface':
                            case 'trait':
                                button_name = name_button(name, type, data);
                                button = make_button(button_name, type, type, name);
                                button.data({"value": data});
                                break;
                        }
                        list.append(button).show();
                    }
                }
            }
        }
    }
    $( function () {
        var wl = $("#whitelist").tooltip();
        wl.tooltip("disable");
        wl.on('keyup', function () {
            list_keyup($(this), "#whitelist_select");
        });
        var bl = $("#blacklist").tooltip();
        bl.tooltip("disable");
        bl.on('keyup', function () {
            list_keyup($(this), "#blacklist_select");
        });
        $("#configuration").accordion({heightStyle: "fill"});
        $("#templates").on('change', function () {
            var template = $(this).val();

            $.getJSON(template, {"template": template}, function (response) {

                load_template(response);
            });
        });

        $("#run, #save").button().on('click', function () {
            sync_code();
            var options = {}, list = function () {
                return {
                    "func": {},
                    "var": {},
                    "global": {},
                    "superglobal": {},
                    "const": {},
                    "magic_const": {},
                    "namespace": {},
                    "alias": {},
                    "class": {},
                    "interface": {},
                    "trait": {},
                    "keyword": {},
                    "operator": {},
                    "primitive": {},
                    "type": {}
                };
            }, whitelist = list(), blacklist = list(), definitions = {
                "func": {},
                "var": {},
                "superglobal": {},
                "const": {},
                "magic_const": {},
                "namespace": {},
                "alias": {},
                "class": {},
                "interface": {},
                "trait": {}
            };
            $("#options").find("input").each(function () {
                var name = $(this).attr('name');
                options[name] = $(this).is(":checked") ? 1 : 0;
            });
            var x, i;
            for (x in whitelist) {
                i = 0;
                $("#whitelist_" + x).find('input').each(function () {
                    var el = $(this), type = el.data('type');
                    whitelist[type][i] = el.data('name');
                    i++;
                });
            }
            for (x in blacklist) {
                i = 0;
                $("#blacklist_" + x).find('input').each(function () {
                    var el = $(this), type = el.data('type');
                    blacklist[type][i] = el.data('name');
                    i++;
                });
            }
            for (x in definitions) {
                i = 0;
                // console.log(definitions);
                $("#define_" + x).find('input').each(function () {
                    var el = $(this), type = el.data('type');
                    switch (type) {
                        case 'func':
                            definitions[type][el.data('name')] = {
                                "args": el.data('args'),
                                "code": el.data('code'),
                                "pass": el.data('pass') ? 1 : 0,
                                "fullcode": el.data('fullcode')
                            };
                            break;
                        case 'var':
                        case 'const':
                        case 'magic_const':
                            definitions[type][el.data('name')] = {
                                "scalar": el.data('scalar'),
                                "value": el.data('value')
                            };
                            break;
                        case 'superglobal':
                            definitions[type][el.data('name')] = {
                                "key": el.data('key'),
                                "scalar": el.data('scalar'),
                                "value": el.data('value')
                            };
                            break;
                        case 'namespace':
                            definitions[type][el.data('name')] = el.data('name');
                            break;
                        case 'alias':
                        case 'class':
                        case 'interface':
                        case 'trait':
                            definitions[type][el.data('name')] = el.data('value');
                            break;
                    }
                    i++;
                });
            }

            options.error_level = $("#error_level").val();
            var cm =$("#coursemodule").val();
  
            
            var coursemodule=$.parseJSON(cm);
           var cmid=coursemodule.id;
           var cminstance=coursemodule.instance;
           var path=coursemodule.path;
         
            if ($(this).attr('id') == 'save') {
        
                var template = $('#templates').val();              
                var title = $('#titlesc').val();

                if (!title)
                    alert("Can't save a file without a name!");
                else if (!code)
                    alert("Can't save a file without a code!, please enter a code");
                else {
                    $.post("sandboxrecord.php", {"instanceid":cminstance, "title": title, "template": template, "code": code, "setup_code": setup_code, "prepend_code": prepend_code, "append_code": append_code, "options": options, "whitelist": whitelist, "blacklist": blacklist, "definitions": definitions, "save": name})
                            .done(function (data) {

                                alert('code saved successfully, please check in saved drop down box');
                                $('#titlesc').val('');
                                if (data == 1)
                                    window.location=path;  
                                else                                 
                                    $("#selectedrecord").append('<option value="' + data + '">' + title + '</option>').val(data);
                                //  alert(data);
                            });

                }
                $.post("./runtime.php?id=".cmid, {"code": code, "setup_code": setup_code, "prepend_code": prepend_code, "append_code": append_code, "options": options, "whitelist": whitelist, "blacklist": blacklist, "definitions": definitions, "save": name}, function (response) {

                    if (response.success) {
                        //$("#templates").append('<option value="templates/' + response.file + '">' + response.name + '</option>').val('templates/' + response.file);
                        $("#templates").val(response.file);
                    }
                }, 'json');
            } else {               
                $.post("./runtime.php?id=".cmid, {"code": JSON.stringify(code), "setup_code": JSON.stringify(setup_code), "prepend_code": JSON.stringify(prepend_code), "append_code": JSON.stringify(append_code), "options": JSON.stringify(options), "whitelist":JSON.stringify( whitelist), "blacklist": JSON.stringify(blacklist), "definitions": JSON.stringify(definitions)}, function (data) {
               //   $.post("./runtime.php?id=".cmid, {"code":code, "setup_code":setup_code, "prepend_code":prepend_code, "append_code":append_code, "options":options, "whitelist":whitelist, "blacklist":blacklist, "definitions":definitions,"options1": JSON.stringify(options),"definitions1": JSON.stringify(definitions)}, function (data) {   
                    $("#output").html(data);
                })
            }
        });
        $("#whitelist_add").on("click", function () {
            var el = $("#whitelist");
            var name = el.val();
            var type = $("#whitelist_select").val();
            var list = $("#whitelist_" + type);
            if (!name) {
                return;
            }
            if (invalid(name, type)) {
                el.tooltip("enable");
                el.tooltip("open");
                return;
            }
            var button_name = name_button(name, type);
            if (list.find('input[value="' + button_name + '"]').length) {
                return;
            }
            list.append(make_button(button_name, "whitelist", type, name)).show();
            el.val('');
        });
        $("#blacklist_add").on("click", function () {
            var el = $("#blacklist");
            var name = el.val();
            var type = $("#blacklist_select").val();
            var list = $("#blacklist_" + type);
            if (!name) {
                return;
            }
            if (invalid(name, type)) {
                el.tooltip("enable");
                el.tooltip("open");
                return;
            }
            var button_name = name_button(name, type);
            if (list.find('input[value="' + button_name + '"]').length) {
                return;
            }
            list.append(make_button(button_name, "blacklist", type, name)).show();
            el.val('');
        });
        $(document).on('click', 'input.whitelist, input.blacklist', function () {
            var el = $(this);
            if (el.parent().children('input').length < 2) {
                el.parent().hide();
            }
            el.remove();
        });
        $("#mode").on('change', function () {
            sync_code();
            switch ($(this).val()) {
                case 'code':
                    editor.setValue(code);
                    current_mode = 'code';
                    break;
                case 'setup_code':
                    editor.setValue(setup_code);
                    current_mode = 'setup_code';
                    break;
                case 'prepend_code':
                    editor.setValue(prepend_code);
                    current_mode = 'prepend_code';
                    break;
                case 'append_code':
                    editor.setValue(append_code);
                    current_mode = 'append_code';
                    break;
            }
            editor.clearSelection();
        });
    });
    $("#define_add").on('click', function () {
        launch_editor($("#define_select").val());
    });
    $(document).on('click', "input.func, input.var, input.superglobal, input.const, input.magic_const, input.namespace, input.alias, input.class, input.interface, input.trait", function () {
        launch_editor($(this).attr('class'), $(this));
    });
    $("#func_editor_name, #func_editor_args").on('keyup', function () {
        if ($(this).attr('id') == 'func_editor_name') {
            $(this).val($(this).val().replace(/[^a-z0-9_\\]+/i, '_'));
        }
        var name = $("#func_editor_name").val(), args = $("#func_editor_args").val();
        $("#func_editor_preview").html('function ' + name + '(' + args + '){');
    });
    function var_preview(name, scalar, value) {
        switch (scalar) {
            case 'int':
                value = value.replace(/[^0-9]+/i, '') || 0;
                break;
            case 'float':
                value = value.replace(/[^0-9.]+/i, '') || 0;
                break;
            case 'bool':
                value = (value.toLowerCase() == 'true' || parseInt(value)) ? 'true' : 'false';
                break;
            case 'null':
                value = 'null';
                break;
        }
        $("#var_editor_value").val(value);
        $("#var_editor_preview").html(name ? ('$' + name + ' = ' + (scalar == 'string' ? "'" + value + "'" : value) + ';') : '');
    }
    $("#var_editor_name").on('keyup', function () {
        $(this).val($(this).val().replace(/[^a-z0-9_]+/i, '_'));
        var_preview($("#var_editor_name").val(), $("#var_editor_scalar").val(), $("#var_editor_value").val());
    });
    $("#var_editor_scalar").on('change', function () {
        var scalar = $("#var_editor_scalar").val(), v = $("#var_editor_value"), value = v.val();
        if (scalar == 'bool') {
            v.replaceWith('<select id="var_editor_value"><option value="true">true</option><option value="false">false</option></select>');
        } else if (scalar == 'null') {
            v.replaceWith('<input type="text" id="var_editor_value" style="width: 100%;" value="" disabled="disabled"/>');
        } else {
            v.replaceWith('<input type="text" id="var_editor_value" style="width: 100%;" value=""/>');
        }
        var_preview($("#var_editor_name").val(), scalar, (scalar == 'string' ? '' : value));
    });
    $(document).on('keyup change', "#var_editor_value", function () {
        var_preview($("#var_editor_name").val(), $("#var_editor_scalar").val(), $("#var_editor_value").val());
    });
    function superglobal_preview(name, key, scalar, value) {
        switch (scalar) {
            case 'int':
                value = value.replace(/[^0-9]+/i, '') || 0;
                break;
            case 'float':
                value = value.replace(/[^0-9.]+/i, '') || 0;
                break;
            case 'bool':
                value = (value.toLowerCase() == 'true' || parseInt(value)) ? 'true' : 'false';
                break;
            case 'null':
                value = 'null';
                break;
        }
        $("#superglobal_editor_value").val(value);
        $("#superglobal_editor_preview").html(name ? ('$' + name + "['" + key + "'] = " + (scalar == 'string' ? "'" + value + "'" : value) + ';') : '');
    }
    $(document).on('keyup change', "#superglobal_editor_name, #superglobal_editor_key, #superglobal_editor_value", function () {
        superglobal_preview($("#superglobal_editor_name").val(), $("#superglobal_editor_key").val(), $("#superglobal_editor_scalar").val(), $("#superglobal_editor_value").val());
    });
    $("#superglobal_editor_scalar").on('change', function () {
        var scalar = $("#superglobal_editor_scalar").val(), v = $("#superglobal_editor_value"), value = v.val();
        if (scalar == 'bool') {
            v.replaceWith('<select id="superglobal_editor_value"><option value="true">true</option><option value="false">false</option></select>');
        } else if (scalar == 'null') {
            v.replaceWith('<input type="text" id="superglobal_editor_value" style="width: 100%;" value="" disabled="disabled"/>');
        } else {
            v.replaceWith('<input type="text" id="superglobal_editor_value" style="width: 100%;" value=""/>');
        }
        superglobal_preview($("#superglobal_editor_name").val(), $("#superglobal_editor_key").val(), scalar, (scalar == 'string' ? '' : value));
    });
    function const_preview(name, scalar, value) {
        switch (scalar) {
            case 'int':
                value = value.replace(/[^0-9]+/i, '') || 0;
                break;
            case 'float':
                value = value.replace(/[^0-9.]+/i, '') || 0;
                break;
            case 'bool':
                value = (value.toLowerCase() == 'true' || parseInt(value)) ? 'true' : 'false';
                break;
            case 'null':
                value = 'null';
                break;
        }
        $("#const_editor_value").val(value);
        $("#const_editor_preview").html(name ? (name.toUpperCase() + ' = ' + (scalar == 'string' ? "'" + value + "'" : value) + ';') : '');
    }
    $("#const_editor_name").on('keyup', function () {
        $(this).val($(this).val().replace(/[^a-z0-9_]+/i, '_').toUpperCase());
        const_preview($("#const_editor_name").val(), $("#const_editor_scalar").val(), $("#const_editor_value").val());
    });
    $("#const_editor_scalar").on('change', function () {
        var scalar = $("#const_editor_scalar").val(), v = $("#const_editor_value"), value = v.val();
        if (scalar == 'bool') {
            v.replaceWith('<select id="const_editor_value"><option value="true">true</option><option value="false">false</option></select>');
        } else if (scalar == 'null') {
            v.replaceWith('<input type="text" id="const_editor_value" style="width: 100%;" value="" disabled="disabled"/>');
        } else {
            v.replaceWith('<input type="text" id="const_editor_value" style="width: 100%;" value=""/>');
        }
        const_preview($("#const_editor_name").val(), scalar, (scalar == 'string' ? '' : value));
    });
    $(document).on('keyup change', "#const_editor_value", function () {
        const_preview($("#const_editor_name").val(), $("#const_editor_scalar").val(), $("#const_editor_value").val());
    });
    function magic_const_preview(name, scalar, value) {
        switch (scalar) {
            case 'int':
                value = value.replace(/[^0-9]+/i, '') || 0;
                break;
            case 'float':
                value = value.replace(/[^0-9.]+/i, '') || 0;
                break;
            case 'bool':
                value = (value.toLowerCase() == 'true' || parseInt(value)) ? 'true' : 'false';
                break;
            case 'null':
                value = 'null';
                break;
        }
        $("#magic_const_editor_value").val(value);
        $("#magic_const_editor_preview").html(name ? (name.toUpperCase() + ' = ' + (scalar == 'string' ? "'" + value + "'" : value) + ';') : '');
    }
    $("#magic_const_editor_name").on('change', function () {
        magic_const_preview($("#magic_const_editor_name").val(), $("#magic_const_editor_scalar").val(), $("#magic_const_editor_value").val());
    });
    $("#magic_const_editor_scalar").on('change', function () {
        var scalar = $("#magic_const_editor_scalar").val(), v = $("#magic_const_editor_value"), value = v.val();
        if (scalar == 'bool') {
            v.replaceWith('<select id="magic_const_editor_value"><option value="true">true</option><option value="false">false</option></select>');
        } else if (scalar == 'null') {
            v.replaceWith('<input type="text" id="magic_const_editor_value" style="width: 100%;" value="" disabled="disabled"/>');
        } else {
            v.replaceWith('<input type="text" id="magic_const_editor_value" style="width: 100%;" value=""/>');
        }
        magic_const_preview($("#magic_const_editor_name").val(), scalar, (scalar == 'string' ? '' : value));
    });
    $(document).on('keyup change', "#magic_const_editor_value", function () {
        magic_const_preview($("#magic_const_editor_name").val(), $("#magic_const_editor_scalar").val(), $("#magic_const_editor_value").val());
    });
    function namespace_preview(name) {
        $("#namespace_editor_preview").html(name ? ('namespace ' + name + ';') : '');
    }
    $("#namespace_editor_name").on('keyup', function () {
        $(this).val($(this).val().replace(/[^a-z0-9_\\]+/i, '_'));
        namespace_preview($("#namespace_editor_name").val());
    });
    function alias_preview(name, value) {
        $("#alias_editor_preview").html(name ? ('use ' + name + (value ? ' as ' + value : '') + ';') : '');
    }
    $("#alias_editor_name, #alias_editor_value").on('keyup', function () {
        $(this).val($(this).val().replace(/[^a-z0-9_\\]+/i, '_'));
        alias_preview($("#alias_editor_name").val(), $("#alias_editor_value").val());
    });
    function class_preview(name, value) {
        $("#class_editor_preview").html(name ? ('new ' + name + (value ? ' => new ' + value : '')) : '');
    }
    $("#class_editor_name, #class_editor_value").on('keyup', function () {
        $(this).val($(this).val().replace(/[^a-z0-9_\\]+/i, '_'));
        class_preview($("#class_editor_name").val(), $("#class_editor_value").val());
    });
    function interface_preview(name, value) {
        $("#interface_editor_preview").html(name ? (name + (value ? ' => ' + value : '')) : '');
    }
    $("#interface_editor_name, #interface_editor_value").on('keyup', function () {
        $(this).val($(this).val().replace(/[^a-z0-9_\\]+/i, '_'));
        interface_preview($("#interface_editor_name").val(), $("#interface_editor_value").val());
    });
    function trait_preview(name, value) {
        $("#trait_editor_preview").html(name ? (name + (value ? ' => ' + value : '')) : '');
    }
    $("#trait_editor_name, #trait_editor_value").on('keyup', function () {
        $(this).val($(this).val().replace(/[^a-z0-9_\\]+/i, '_'));
        trait_preview($("#trait_editor_name").val(), $("#trait_editor_value").val());
    });
    var func_editor;
    function launch_editor(type, el) {
        var dialog = $("#" + type + "_editor_dialog"), buttons, delete_func = function () {
            if (el.parent().children('input').length < 2) {
                el.parent().hide();
            }
            el.remove();
            $(this).dialog("close");
        };

        switch (type) {
            case 'func':
                buttons = {
                    "Save": function () {
                        var name = $("#func_editor_name").val(),
                                args = $("#func_editor_args").val(),
                                pass = $("#func_editor_pass").is(":checked") ? 1 : 0,
                                code = func_editor.getValue(),
                                button_name = name_button(name, "func"),
                                button = make_button(button_name, "func", "func", name),
                                list = $("#define_func");
                        button.data({"args": args, "code": code, "pass": pass, "fullcode": 'function(' + args + '){' + code + '}'});
                        if (el) {
                            el.replaceWith(button);
                        } else {
                            list.append(button).show();
                        }
                        $(this).dialog("close");
                    }
                };
                if (el) {
                    buttons["Delete"] = delete_func;
                }
                dialog.dialog({
                    position: "center",
                    height: 580,
                    width: 800,
                    buttons: buttons,
                    close: function () {
                        $("#func_editor_name, #func_editor_args").val('');
                        $("#func_editor_pass").prop("checked", false);
                        $("#func_editor_preview").html('function (){');
                        $("#func_editor_dialog").hide().dialog("destroy");
                    }}).show();
                func_editor = ace.edit("func_editor");
                func_editor.setTheme("ace/theme/github");
                func_editor.getSession().setMode("ace/mode/php");
                func_editor.setValue('');
                func_editor.clearSelection();
                if (el) {
                    var name = el.data('name'), args = el.data('args'), pass = el.data('pass'), code = el.data('code');
                    $("#func_editor_name").val(name);
                    $("#func_editor_args").val(args);
                    $("#func_editor_pass").prop("checked", pass ? true : false);
                    $("#func_editor_preview").html('function ' + name + '(' + args + '){');
                    func_editor.setValue(code);
                    func_editor.clearSelection();
                }
                break;

            case 'var':
                buttons = {
                    "Save": function () {
                        var name = $("#var_editor_name").val(),
                                scalar = $("#var_editor_scalar").val(),
                                value = $("#var_editor_value").val(),
                                button_name = name_button(name, "var"),
                                button = make_button(button_name, "var", "var", name),
                                list = $("#define_var");
                        switch (scalar) {
                            case 'int':
                                value = parseInt(value) || 0;
                                break;
                            case 'float':
                                value = parseFloat(value) || 0;
                                break;
                            case 'bool':
                                value = (value.toLowerCase() == 'true' || parseInt(value)) ? true : false;
                                break;
                            case 'null':
                                value = null;
                                break;
                        }
                        button.data({"scalar": scalar, "value": value});
                        if (el) {
                            el.replaceWith(button);
                        } else {
                            list.append(button).show();
                        }
                        $(this).dialog("close");
                    }
                };
                if (el) {
                    buttons["Delete"] = delete_func;
                }
                dialog.dialog({
                    position: "center",
                    height: 300,
                    width: 400,
                    buttons: buttons,
                    close: function () {
                        $("#var_editor_name, #var_editor_value").val('');
                        $("#var_editor_scalar").val('string');
                        $("#var_editor_preview").html('');
                        $("#var_editor_dialog").hide().dialog("destroy");
                    }}).show();
                if (el) {
                    var value = el.data('value');
                    if (value !== null) {
                        value = value.toString();
                    }
                    $("#var_editor_name").val(el.data('name'));
                    $("#var_editor_scalar").val(el.data('scalar'));
                    $("#var_editor_value").val(value);
                    var_preview(el.data('name'), el.data('scalar'), value);
                }
                break;

            case 'superglobal':
                buttons = {
                    "Save": function () {
                        var name = $("#superglobal_editor_name").val(),
                                key = $("#superglobal_editor_key").val(),
                                scalar = $("#superglobal_editor_scalar").val(),
                                value = $("#superglobal_editor_value").val(),
                                button_name = name_button(name, "superglobal", key),
                                button = make_button(button_name, "superglobal", "superglobal", name),
                                list = $("#define_superglobal");
                        switch (scalar) {
                            case 'int':
                                value = parseInt(value) || 0;
                                break;
                            case 'float':
                                value = parseFloat(value) || 0;
                                break;
                            case 'bool':
                                value = (value.toLowerCase() == 'true' || parseInt(value)) ? true : false;
                                break;
                            case 'null':
                                value = null;
                                break;
                        }
                        button.data({"key": key, "scalar": scalar, "value": value});
                        if (el) {
                            el.replaceWith(button);
                        } else {
                            list.append(button).show();
                        }
                        $(this).dialog("close");
                    }
                };
                if (el) {
                    buttons["Delete"] = delete_func;
                }
                dialog.dialog({
                    position: "center",
                    height: 360,
                    width: 400,
                    buttons: buttons,
                    close: function () {
                        $("#superglobal_editor_name").val('_GET');
                        $("#superglobal_editor_key, #superglobal_editor_value").val('');
                        $("#superglobal_editor_scalar").val('string');
                        $("#superglobal_editor_preview").html('');
                        $("#superglobal_editor_dialog").hide().dialog("destroy");
                    }}).show();
                if (el) {
                    var value = el.data('value');
                    if (value !== null) {
                        value = value.toString();
                    }
                    $("#superglobal_editor_name").val(el.data('name'));
                    $("#superglobal_editor_key").val(el.data('key'));
                    $("#superglobal_editor_scalar").val(el.data('scalar'));
                    $("#superglobal_editor_value").val(value);
                    superglobal_preview(el.data('name'), el.data('key'), el.data('scalar'), value);
                }
                break;

            case 'const':
                buttons = {
                    "Save": function () {
                        var name = $("#const_editor_name").val(),
                                scalar = $("#const_editor_scalar").val(),
                                value = $("#const_editor_value").val(),
                                button_name = name_button(name, "const"),
                                button = make_button(button_name, "const", "const", name),
                                list = $("#define_const");
                        switch (scalar) {
                            case 'int':
                                value = parseInt(value) || 0;
                                break;
                            case 'float':
                                value = parseFloat(value) || 0;
                                break;
                            case 'bool':
                                value = (value.toLowerCase() == 'true' || parseInt(value)) ? true : false;
                                break;
                            case 'null':
                                value = null;
                                break;
                        }
                        button.data({"scalar": scalar, "value": value});
                        if (el) {
                            el.replaceWith(button);
                        } else {
                            list.append(button).show();
                        }
                        $(this).dialog("close");
                    }
                };
                if (el) {
                    buttons["Delete"] = delete_func;
                }
                dialog.dialog({
                    position: "center",
                    height: 300,
                    width: 400,
                    buttons: buttons,
                    close: function () {
                        $("#const_editor_name, #const_editor_value").val('');
                        $("#const_editor_scalar").val('string');
                        $("#const_editor_preview").html('');
                        $("#const_editor_dialog").hide().dialog("destroy");
                    }}).show();
                if (el) {
                    var value = el.data('value');
                    if (value !== null) {
                        value = value.toString();
                    }
                    $("#const_editor_name").val(el.data('name'));
                    $("#const_editor_scalar").val(el.data('scalar'));
                    $("#const_editor_value").val(value);
                    const_preview(el.data('name'), el.data('scalar'), value);
                }
                break;

            case 'magic_const':
                buttons = {
                    "Save": function () {
                        var name = $("#magic_const_editor_name").val(),
                                scalar = $("#magic_const_editor_scalar").val(),
                                value = $("#magic_const_editor_value").val(),
                                button_name = name_button(name, "magic_const"),
                                button = make_button(button_name, "magic_const", "magic_const", name),
                                list = $("#define_magic_const");
                        switch (scalar) {
                            case 'int':
                                value = parseInt(value) || 0;
                                break;
                            case 'float':
                                value = parseFloat(value) || 0;
                                break;
                            case 'bool':
                                value = (value.toLowerCase() == 'true' || parseInt(value)) ? true : false;
                                break;
                            case 'null':
                                value = null;
                                break;
                        }
                        button.data({"scalar": scalar, "value": value});
                        if (el) {
                            el.replaceWith(button);
                        } else {
                            list.append(button).show();
                        }
                        $(this).dialog("close");
                    }
                };
                if (el) {
                    buttons["Delete"] = delete_func;
                }
                dialog.dialog({
                    position: "center",
                    height: 300,
                    width: 400,
                    buttons: buttons,
                    close: function () {
                        $("#magic_const_editor_name, #magic_const_editor_value").val('');
                        $("#magic_const_editor_scalar").val('string');
                        $("#magic_const_editor_preview").html('');
                        $("#magic_const_editor_dialog").hide().dialog("destroy");
                    }}).show();
                if (el) {
                    var value = el.data('value');
                    if (value !== null) {
                        value = value.toString();
                    }
                    $("#magic_const_editor_name").val(el.data('name'));
                    $("#magic_const_editor_scalar").val(el.data('scalar'));
                    $("#magic_const_editor_value").val(value);
                    magic_const_preview(el.data('name'), el.data('scalar'), value);
                }
                break;

            case 'namespace':
                buttons = {
                    "Save": function () {
                        var name = $("#namespace_editor_name").val(),
                                button_name = name_button(name, "namespace"),
                                button = make_button(button_name, "namespace", "namespace", name),
                                list = $("#define_namespace");
                        if (el) {
                            el.replaceWith(button);
                        } else {
                            list.append(button).show();
                        }
                        $(this).dialog("close");
                    }
                };
                if (el) {
                    buttons["Delete"] = delete_func;
                }
                dialog.dialog({
                    position: "center",
                    height: 300,
                    width: 400,
                    buttons: buttons,
                    close: function () {
                        $("#namespace_editor_name").val('');
                        $("#namespace_editor_preview").html('');
                        $("#namespace_editor_dialog").hide().dialog("destroy");
                    }}).show();
                if (el) {
                    $("#namespace_editor_name").val(el.data('name'));
                    namespace_preview(el.data('name'));
                }
                break;

            case 'alias':
                buttons = {
                    "Save": function () {
                        var name = $("#alias_editor_name").val(),
                                value = $("#alias_editor_value").val(),
                                button_name = name_button(name, "alias", value),
                                button = make_button(button_name, "alias", "alias", name),
                                list = $("#define_alias");
                        button.data({"value": value});
                        if (el) {
                            el.replaceWith(button);
                        } else {
                            list.append(button).show();
                        }
                        $(this).dialog("close");
                    }
                };
                if (el) {
                    buttons["Delete"] = delete_func;
                }
                dialog.dialog({
                    position: "center",
                    height: 300,
                    width: 400,
                    buttons: buttons,
                    close: function () {
                        $("#alias_editor_name, #alias_editor_value").val('');
                        $("#alias_editor_preview").html('');
                        $("#alias_editor_dialog").hide().dialog("destroy");
                    }}).show();
                if (el) {
                    var value = el.data('value');
                    if (value !== null) {
                        value = value.toString();
                    }
                    $("#alias_editor_name").val(el.data('name'));
                    $("#alias_editor_value").val(value);
                    alias_preview(el.data('name'), value);
                }
                break;

            case 'class':
                buttons = {
                    "Save": function () {
                        var name = $("#class_editor_name").val(),
                                value = $("#class_editor_value").val(),
                                button_name = name_button(name, "class", value),
                                button = make_button(button_name, "class", "class", name),
                                list = $("#define_class");
                        button.data({"value": value});
                        if (el) {
                            el.replaceWith(button);
                        } else {
                            list.append(button).show();
                        }
                        $(this).dialog("close");
                    }
                };
                if (el) {
                    buttons["Delete"] = delete_func;
                }
                dialog.dialog({
                    position: "center",
                    height: 300,
                    width: 400,
                    buttons: buttons,
                    close: function () {
                        $("#class_editor_name, #class_editor_value").val('');
                        $("#class_editor_preview").html('');
                        $("#class_editor_dialog").hide().dialog("destroy");
                    }}).show();
                if (el) {
                    var value = el.data('value');
                    if (value !== null) {
                        value = value.toString();
                    }
                    $("#class_editor_name").val(el.data('name'));
                    $("#class_editor_value").val(value);
                    class_preview(el.data('name'), value);
                }
                break;

            case 'interface':
                buttons = {
                    "Save": function () {
                        var name = $("#interface_editor_name").val(),
                                value = $("#interface_editor_value").val(),
                                button_name = name_button(name, "interface", value),
                                button = make_button(button_name, "interface", "interface", name),
                                list = $("#define_interface");
                        button.data({"value": value});
                        if (el) {
                            el.replaceWith(button);
                        } else {
                            list.append(button).show();
                        }
                        $(this).dialog("close");
                    }
                };
                if (el) {
                    buttons["Delete"] = delete_func;
                }
                dialog.dialog({
                    position: "center",
                    height: 300,
                    width: 400,
                    buttons: buttons,
                    close: function () {
                        $("#interface_editor_name, #interface_editor_value").val('');
                        $("#interface_editor_preview").html('');
                        $("#interface_editor_dialog").hide().dialog("destroy");
                    }}).show();
                if (el) {
                    var value = el.data('value');
                    if (value !== null) {
                        value = value.toString();
                    }
                    $("#interface_editor_name").val(el.data('name'));
                    $("#interface_editor_value").val(value);
                    interface_preview(el.data('name'), value);
                }
                break;

            case 'trait':
                buttons = {
                    "Save": function () {
                        var name = $("#trait_editor_name").val(),
                                value = $("#trait_editor_value").val(),
                                button_name = name_button(name, "trait", value),
                                button = make_button(button_name, "trait", "trait", name),
                                list = $("#define_trait");
                        button.data({"value": value});
                        if (el) {
                            el.replaceWith(button);
                        } else {
                            list.append(button).show();
                        }
                        $(this).dialog("close");
                    }
                };
                if (el) {
                    buttons["Delete"] = delete_func;
                }
                dialog.dialog({
                    position: "center",
                    height: 300,
                    width: 400,
                    buttons: buttons,
                    close: function () {
                        $("#trait_editor_name, #trait_editor_value").val('');
                        $("#trait_editor_preview").html('');
                        $("#trait_editor_dialog").hide().dialog("destroy");
                    }}).show();
                if (el) {
                    var value = el.data('value');
                    if (value !== null) {
                        value = value.toString();
                    }
                    $("#trait_editor_name").val(el.data('name'));
                    $("#trait_editor_value").val(value);
                    trait_preview(el.data('name'), value);
                }
                break;
        }
    }
    $(function () {
        $("input.load").button().on('click', function () {
            $('#load').contents().find('input[name="load"]').click();
        });
        $("a.help").on('click', function () {
            $("#help_dialog").dialog({
                position: "center",
                width: 800,
                close: function () {
                    $("#help_dialog").hide().dialog("destroy");
                }
            }).show();
        });
    });

    $("#share").click(function () {
        $("#sharediv").slideToggle("slow");
    });

    $("#selectedrecord").change(function () {

        var id = $("#selectedrecord option:selected").val();
        $.post("sandboxrecord.php", {"selectedid": id})
                .done(function (response) {
                    var re = /\{(?:[^{}])*\}/;
                    // console.log(data);
                    // $.getJSON(data, function(response){
                    //  jQuery.parseJSON(data);
                    //alert(response) ; 
                    load_template(response);
                    //  });
                    //load_template(data);
                });
    });





