<?php

declare(strict_types=1);

$directory = __DIR__ . '/generated';
if (!is_dir($directory)) {
    mkdir($directory, 0777, true);
}

foreach (['WithImports' => true, 'WithoutImports' => false] as $class => $withImports) {
    $source = "<?php\n\nnamespace App\\Model;\n\n";
    if ($withImports) {
        for ($i = 0; $i < 572; $i++) {
            $source .= "use App\\Model\\Model$i;\n";
        }
    }
    $source .= "\nfinal class $class\n{\n";
    for ($i = 0; $i < 1150; $i++) {
        $model = $i % 572;
        $source .= "    /** @return Model$model */\n";
        $source .= "    public function get$i(): object { return new \\stdClass(); }\n\n";
    }
    $source .= "}\n";
    file_put_contents("$directory/$class.php", $source);
}

foreach (['WithImports', 'WithoutImports'] as $class) {
    file_put_contents("$directory/Use$class.php", "<?php function use$class(\\App\\Model\\$class \$value): object { return \$value->get1(); }\n");
    $config = "parameters:\n    level: 0\n    scanFiles: [$class.php]\n    tmpDir: ../cache/$class\n";
    file_put_contents("$directory/phpstan-$class.neon", $config);
}

echo "Generated two classes with the same 1150 documented methods.\n";
