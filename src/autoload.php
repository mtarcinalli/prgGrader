<?php

function autoloader($class) {
	$class = strtolower($class);
	if ($class == "form0" || $class == "obj2db") {
		include("../src/modules/obj2db/src/" . $class . ".php");
		return;
	}
	include("../src/classes/" . $class . ".php");
}
spl_autoload_register("autoloader");
