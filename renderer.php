<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class phpsandbox implements renderable {

    // it store saved phpsandbox code
    public $data;
    // it store course module info
    public $cm;

    public function __construct(array $data, $cm) {
        // here the widget is prepared and all necessary logic is performed
        $this->data = $data;
        $this->cm = $cm;
    }

}

class mod_phpsandbox_renderer extends plugin_renderer_base {

    protected function render_phpsandbox(phpsandbox $ps) {

        $this->ps_required_js($ps->data);
        $this->ps_editor_view($ps->data, $ps->cm);
        $this->configuration_settingsview($ps->data, $ps->cm);
    }

    public function ps_required_js($data) {
        global $PAGE, $DB, $USER;
        // print_object($this->data);

       // $PAGE->requires->js('/mod/phpsandbox/js/jquery-1.9.1.js');
      //  $PAGE->requires->js($PAGE->requires->jquery_plugin('ui');'/mod/phpsandbox/js/jquery-ui.js');
         //$PAGE->requires->jquery();
         // $PAGE->requires->jquery_plugin('ui');
          
          
        $PAGE->requires->js('/mod/phpsandbox/js/pscustom.js');
        $code = json_encode(isset($data['code']) ? $data['code'] : '');
        $setup_code = json_encode(isset($data['setup_code']) ? $data['setup_code'] : '');
        $prepend_code = json_encode(isset($data['prepend_code']) ? $data['prepend_code'] : '');
        $append_code = json_encode(isset($data['append_code']) ? $data['append_code'] : '');

        $script = "var code =$code,
                    setup_code = $setup_code,
                    prepend_code = $prepend_code,
                    append_code =  $append_code,
                    current_mode = 'code';";
        echo html_writer::script($script);
        $PAGE->requires->js('/mod/phpsandbox/js/ace.js');
        ?>
        <!--<script src="http://code.jquery.com/ui/1.10.1/jquery-ui.js" type="text/javascript" charset="utf-8"></script>-->
    <!--    <script src="http://d1n0x3qji82z53.cloudfront.net/src-min-noconflict/ace.js" type="text/javascript" charset="utf-8"></script>-->
        <?php
        $PAGE->requires->js('/mod/phpsandbox/js/psandbox.js');
    }

    public function ps_editor_view($data, $cm) {
        global $DB, $USER, $CFG;


        $coursemodule = new stdClass();
        $coursemodule->id = $cm->id;
        $coursemodule->instance = $cm->instance;
        $coursemodule->path=$CFG->wwwroot.'/mod/phpsandbox/view.php?id='.$cm->id;

        echo '<div>
            <select id="templates">
                <option>Choose Template </option>';

        foreach (glob("templates/*.json") as $index => $template) {
            echo '<option value="' . htmlentities($template) . '">' . ($index + 1) . ' - ' . htmlentities(substr($template, 16, strlen($template) - 21)) . '</option>';
        }

        echo '</select>';

        if (is_siteadmin())
            $instancelist = $DB->get_records('phpsandbox_records', array('sandboxinstanceid' => $cm->instance));
        else
            $instancelist = $DB->get_records('phpsandbox_records', array('sandboxinstanceid' => $cm->instance, userid => $USER->id));
        if (count($instancelist) > 0) {
            echo '<select id="selectedrecord" >';
            echo '<option value=0>select saved record </option>';
            foreach ($instancelist as $instance) {
                echo '<option value=' . $instance->id . '>' . $instance->name . '</option>';
            }
            echo '</select>';
        }

        echo '<div id="instructions">
                <span id="mode_container">
                    <!--<label for="mode"><strong>Editor Mode:</strong></label>-->
                    <select id="mode">
                        <option value=0>Editor mode </option>
                        <option value="code">Sandboxed Code</option>
                        <optgroup label="Trusted Code">
                            <option value="setup_code">Setup Code (runs before code and outside sandbox)</option>
                            <option value="prepend_code">Prepended Code (runs before code inside sandbox)</option>
                            <option value="append_code">Appended Code (runs after code inside sandbox)</option>
                        </optgroup>
                    </select>
                </span>    
            </div>

            <div id="iocontent">
                <h6>Input : </h6>
                <div id="editor">';

        echo isset($data['code']) ? $data['code'] : '';
        echo'</div>
                    <div id="sharediv">
                        <input type="text" id="titlesc"  name="titlesc" placeholder="Save the code"></input>
                        <input type="button" value="Save" id="save" style=""/>
                        <input type="hidden" value=' . json_encode($coursemodule) . '  id="coursemodule"  name="coursemodule"/>
                        <input type="button" value="Run" id="run"/>
                    </div>   
                <div id="phpsandbox_output_container">
                    <h6>Output : </h6>
                    <div id="output" class="ui-widget ui-widget-content ui-corner-all"><pre>Hello World!</pre>


                    </div>
                    <select id="error_level">';

        foreach (array('E_ALL' => E_ALL, 'E_ERROR' => E_ERROR, 'E_WARNING' => E_WARNING, 'E_NONE' => 0, 'E_Error Level' => NULL) as $name => $level) {
            echo '<option value="' . $level . '"' . (error_reporting() == $level ? ' selected="selected"' : '') . '>' . ucfirst(strtolower(substr($name, 2))) . '</option>';
        }

        echo'</select>
                </div>
            </div> ';

//editor settings ends 
    }

    public function configuration_settingsview($data, $cm) {
        global $PAGE, $DB, $USER;
        //  require_once('vendor/autoload.php');
        //         require_once('custom.php');
        //$PAGE->requires->js('/mod/phpsandbox/js/psace.js');
        echo'   <div id="configuration_container">
                <div id="configuration">
                    <h3>Options</h3>
                    <div id="options">';

        $sandbox = new \PHPSandbox\PHPSandbox;
        foreach ($sandbox as $name => $flag) {
            if (is_bool($flag)) {
                $lastname = strstr($name, '_', true);
                $name = htmlentities($name);
                echo '<input type="checkbox" value="true" name="' . $name . '" id="' . $name . '"' . ($flag ? ' checked="checked"' : '') . '/>';
                echo '<label for="' . $name . '">' . htmlentities(ucwords(str_replace(array('_', 'funcs', 'vars'), array(' ', 'functions', 'variables'), $name))) . '</label><br/>';
            }
        }

        echo'</div>
                    <h3>Whitelists</h3>
                    <div>
                        <strong>Add To: </strong>
                        <select id="whitelist_select" style="margin-bottom: 3px;">
                            <option value="func">Functions</option>
                            <option value="var">Variables</option>
                            <option value="global">Globals</option>
                            <option value="superglobal">Superglobals</option>
                            <option value="const">Constants</option>
                            <option value="magic_const">Magic Constants</option>
                            <option value="namespace">Namespaces</option>
                            <option value="alias">Aliases (aka Use)</option>
                            <option value="class">Classes</option>
                            <option value="interface">Interfaces</option>
                            <option value="trait">Traits</option>
                            <option value="keyword">Keywords</option>
                            <option value="operator">Operators</option>
                            <option value="primitive">Primitives</option>
                            <option value="type">Types</option>
                        </select>
                        <br/>
                        <input type="text" id="whitelist" value="" title="Invalid name for whitelisted item!"/>
                        <input type="button" value="+" id="whitelist_add"/>
                        <br/>
                        <strong style="font-size: 9px;">NOTE: Whitelists override blacklists.</strong>
                        <hr class="hr"/>
                        <div id="whitelist_func" style="display: none;">
                            <strong>Functions:</strong>
                        </div>
                        <div id="whitelist_var" style="display: none;">
                            <strong>Variables:</strong>
                        </div>
                        <div id="whitelist_global" style="display: none;">
                            <strong>Globals:</strong>
                        </div>
                        <div id="whitelist_superglobal" style="display: none;">
                            <strong>Superglobals:</strong>
                        </div>
                        <div id="whitelist_const" style="display: none;">
                            <strong>Constants:</strong>
                        </div>
                        <div id="whitelist_magic_const" style="display: none;">
                            <strong>Magic Constants:</strong>
                        </div>
                        <div id="whitelist_namespace" style="display: none;">
                            <strong>Namespaces:</strong>
                        </div>
                        <div id="whitelist_alias" style="display: none;">
                            <strong>Aliases (aka Use):</strong>
                        </div>
                        <div id="whitelist_class" style="display: none;">
                            <strong>Classes:</strong>
                        </div>
                        <div id="whitelist_interface" style="display: none;">
                            <strong>Interfaces:</strong>
                        </div>
                        <div id="whitelist_trait" style="display: none;">
                            <strong>Traits:</strong>
                        </div>
                        <div id="whitelist_keyword" style="display: none;">
                            <strong>Keywords:</strong>
                        </div>
                        <div id="whitelist_operator" style="display: none;">
                            <strong>Operators:</strong>
                        </div>
                        <div id="whitelist_primitive" style="display: none;">
                            <strong>Primitives:</strong>
                        </div>
                        <div id="whitelist_type" style="display: none;">
                            <strong>Types:</strong>
                        </div>
                    </div>
                    <h3>Blacklists</h3>
                    <div>
                        <strong>Add To: </strong>
                        <select id="blacklist_select" style="margin-bottom: 3px;">
                            <option value="func">Functions</option>
                            <option value="var">Variables</option>
                            <option value="global">Globals</option>
                            <option value="superglobal">Superglobals</option>
                            <option value="const">Constants</option>
                            <option value="magic_const">Magic Constants</option>
                            <option value="namespace">Namespaces</option>
                            <option value="alias">Aliases (aka Use)</option>
                            <option value="class">Classes</option>
                            <option value="interface">Interfaces</option>
                            <option value="trait">Traits</option>
                            <option value="keyword">Keywords</option>
                            <option value="operator">Operators</option>
                            <option value="primitive">Primitives</option>
                            <option value="type">Types</option>
                        </select>
                        <br/>
                        <input type="text" id="blacklist" value="" title="Invalid name for blacklisted item!"/>
                        <input type="button" value="+" id="blacklist_add"/>
                        <br/>
                        <strong style="font-size: 9px;">NOTE: Whitelists override blacklists.</strong>
                        <hr class="hr"/>
                        <div id="blacklist_func" style="display: none;">
                            <strong>Functions:</strong>
                        </div>
                        <div id="blacklist_var" style="display: none;">
                            <strong>Variables:</strong>
                        </div>
                        <div id="blacklist_global" style="display: none;">
                            <strong>Globals:</strong>
                        </div>
                        <div id="blacklist_superglobal" style="display: none;">
                            <strong>Superglobals:</strong>
                        </div>
                        <div id="blacklist_const" style="display: none;">
                            <strong>Constants:</strong>
                        </div>
                        <div id="blacklist_magic_const" style="display: none;">
                            <strong>Magic Constants:</strong>
                        </div>
                        <div id="blacklist_namespace" style="display: none;">
                            <strong>Namespaces:</strong>
                        </div>
                        <div id="blacklist_alias" style="display: none;">
                            <strong>Aliases (aka Use):</strong>
                        </div>
                        <div id="blacklist_class" style="display: none;">
                            <strong>Classes:</strong>
                        </div>
                        <div id="blacklist_interface" style="display: none;">
                            <strong>Interfaces:</strong>
                        </div>
                        <div id="blacklist_trait" style="display: none;">
                            <strong>Traits:</strong>
                        </div>
                        <div id="blacklist_keyword" style="display: none;">
                            <strong>Keywords:</strong>
                        </div>
                        <div id="blacklist_operator" style="display: none;">
                            <strong>Operators:</strong>
                        </div>
                        <div id="blacklist_primitive" style="display: none;">
                            <strong>Primitives:</strong>
                        </div>
                        <div id="blacklist_type" style="display: none;">
                            <strong>Types:</strong>
                        </div>
                    </div>
                    <h3>Definitions</h3>
                    <div>
                        <select id="define_select" style="margin-bottom: 3px;">
                            <option value="func">Function</option>
                            <option value="var">Variable</option>
                            <option value="superglobal">Superglobal</option>
                            <option value="const">Constant</option>
                            <option value="magic_const">Magic Constant</option>
                            <option value="namespace">Namespace</option>
                            <option value="alias">Alias (aka Use)</option>
                            <option value="class">Class</option>
                            <option value="interface">Interface</option>
                            <option value="trait">Trait</option>
                        </select>
                        <input type="button" value="Define" id="define_add"/>
                        <br/>
                        <strong style="font-size: 9px;">NOTE: Definitions override both whitelists and blacklists, and are inherently trusted.</strong>
                        <hr class="hr"/>
                        <div id="define_func" style="display: none;">
                            <strong>Functions:</strong>
                        </div>
                        <div id="define_var" style="display: none;">
                            <strong>Variables:</strong>
                        </div>
                        <div id="define_superglobal" style="display: none;">
                            <strong>Superglobals:</strong>
                        </div>
                        <div id="define_const" style="display: none;">
                            <strong>Constants:</strong>
                        </div>
                        <div id="define_magic_const" style="display: none;">
                            <strong>Magic Constants:</strong>
                        </div>
                        <div id="define_namespace" style="display: none;">
                            <strong>Namespaces:</strong>
                        </div>
                        <div id="define_alias" style="display: none;">
                            <strong>Aliases (aka Use):</strong>
                        </div>
                        <div id="define_class" style="display: none;">
                            <strong>Classes:</strong>
                        </div>
                        <div id="define_interface" style="display: none;">
                            <strong>Interfaces:</strong>
                        </div>
                        <div id="define_trait" style="display: none;">
                            <strong>Traits:</strong>
                        </div>
                    </div>
                </div>
            </div>

          <div id="func_editor_dialog" title="Defined Function Editor">
                <span style="float: right;">
                    <label>
                        <strong>Pass PHPSandbox instance to this function?</strong>
                    </label>
                    <input type="checkbox" id="func_editor_pass" value="1"/>
                </span>
                <strong>Function Name: </strong>
                <br/>
                <input type="text" id="func_editor_name" style="width: 100%;" value=""/>
                <br/>
                <br/>
                <strong>Enter function arguments below in <em>$argument = value</em> format, e.g. <em>$foo = "Hello", $bar = "World"</em></strong>
                <br/>
                <input type="text" id="func_editor_args" style="width: 100%;" value=""/>
                <pre id="func_editor_preview">function (){</pre>
                <div id="func_editor" style="width: 780px; height: 300px;"></div>
                <pre style="margin-top: 324px;">}</pre>
            </div>
            <div id="var_editor_dialog" title="Defined Variable Editor">
                <strong>Variable Name: </strong>
                <br/>
                <input type="text" id="var_editor_name" style="width: 100%;" value=""/>
                <br/>
                <br/>
                <strong>Variable Type: </strong>
                <br/>
                <select id="var_editor_scalar">
                    <option value="string">String</option>
                    <option value="bool">Boolean</option>
                    <option value="int">Integer</option>
                    <option value="float">Float</option>
                    <option value="null">Null</option>
                </select>
                <br/>
                <br/>
                <strong>Variable Value: </strong>
                <br/>
                <input type="text" id="var_editor_value" style="width: 100%;" value=""/>
                <pre id="var_editor_preview"></pre>
            </div>
            <div id="superglobal_editor_dialog" title="Defined Superglobal Editor">
                <strong>Superglobal: </strong>
                <br/>
                <select id="superglobal_editor_name">';

        foreach (\PHPSandbox\PHPSandbox::$superglobals as $superglobal) {
            ?><option value="<?= ltrim($superglobal, '_'); ?>">$<?= $superglobal ?></option><?php
        }

        echo'</select>
                <br/>
                <br/>
                <strong>Key: </strong>
                <br/>
                <br/>
                <input type="text" id="superglobal_editor_key" style="width: 100%;" value=""/>
                <br/>
                <br/>
                <strong>Superglobal Type: </strong>
                <br/>
                <select id="superglobal_editor_scalar">
                    <option value="string">String</option>
                    <option value="bool">Boolean</option>
                    <option value="int">Integer</option>
                    <option value="float">Float</option>
                    <option value="null">Null</option>
                </select>
                <br/>
                <br/>
                <strong>Superglobal Value: </strong>
                <br/>
                <input type="text" id="superglobal_editor_value" style="width: 100%;" value=""/>
                <pre id="superglobal_editor_preview"></pre>
            </div>
            <div id="const_editor_dialog" title="Defined Constant Editor">
                <strong>Constant Name: </strong>
                <br/>
                <input type="text" id="const_editor_name" style="width: 100%;" value=""/>
                <br/>
                <br/>
                <strong>Constant Type: </strong>
                <br/>
                <select id="const_editor_scalar">
                    <option value="string">String</option>
                    <option value="bool">Boolean</option>
                    <option value="int">Integer</option>
                    <option value="float">Float</option>
                    <option value="null">Null</option>
                </select>
                <br/>
                <br/>
                <strong>Constant Value: </strong>
                <br/>
                <input type="text" id="const_editor_value" style="width: 100%;" value=""/>
                <pre id="const_editor_preview"></pre>
            </div>
            <div id="magic_const_editor_dialog" title="Defined Magic Constant Editor">
                <strong>Magic Constant: </strong>
                <br/>
                <select id="magic_const_editor_name">';

        foreach (\PHPSandbox\PHPSandbox::$magic_constants as $magic_constant) {
            ?><option value="<?= $magic_constant ?>"><?= $magic_constant ?></option><?php
        }
        echo'</select>
                <br/>
                <br/>
                <strong>Magic Constant Type: </strong>
                <br/>
                <select id="magic_const_editor_scalar">
                    <option value="string">String</option>
                    <option value="bool">Boolean</option>
                    <option value="int">Integer</option>
                    <option value="float">Float</option>
                    <option value="null">Null</option>
                </select>
                <br/>
                <br/>
                <strong>Magic Constant Value: </strong>
                <br/>
                <input type="text" id="magic_const_editor_value" style="width: 100%;" value=""/>
                <pre id="magic_const_editor_preview"></pre>
            </div>
            <div id="namespace_editor_dialog" title="Defined Namespace Editor">
                <strong>Namespace: </strong>
                <br/>
                <input type="text" id="namespace_editor_name" style="width: 100%;" value=""/>
                <pre id="namespace_editor_preview"></pre>
            </div>
            <div id="alias_editor_dialog" title="Defined Alias (aka Use) Editor">
                <strong>Use: </strong>
                <br/>
                <input type="text" id="alias_editor_name" style="width: 100%;" value=""/>
                <br/>
                <br/>
                <strong>Alias (leave blank for none): </strong>
                <br/>
                <input type="text" id="alias_editor_value" style="width: 100%;" value=""/>
                <pre id="alias_editor_preview"></pre>
            </div>
            <div id="class_editor_dialog" title="Defined Class Editor">
                <strong>Redefine Class: </strong>
                <br/>
                <input type="text" id="class_editor_name" style="width: 100%;" value=""/>
                <br/>
                <br/>
                <strong>To: </strong>
                <br/>
                <input type="text" id="class_editor_value" style="width: 100%;" value=""/>
                <pre id="class_editor_preview"></pre>
            </div>
            <div id="interface_editor_dialog" title="Defined Interface Editor">
                <strong>Redefine Interface: </strong>
                <br/>
                <input type="text" id="interface_editor_name" style="width: 100%;" value=""/>
                <br/>
                <br/>
                <strong>To: </strong>
                <br/>
                <input type="text" id="interface_editor_value" style="width: 100%;" value=""/>
                <pre id="interface_editor_preview"></pre>
            </div>
            <div id="trait_editor_dialog" title="Defined Trait Editor">
                <strong>Redefine Trait: </strong>
                <br/>
                <input type="text" id="trait_editor_name" style="width: 100%;" value=""/>
                <br/>
                <br/>
                <strong>To: </strong>
                <br/>
                <input type="text" id="trait_editor_value" style="width: 100%;" value=""/>
                <pre id="trait_editor_preview"></pre>
            </div>';


        // include('custom.php');

        $PAGE->requires->js('/mod/phpsandbox/js/psandbox.js');
    }

}
?>