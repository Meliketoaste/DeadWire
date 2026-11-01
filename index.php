<?php
session_start();
abstract class Component {
    #[Url] public static ?string $_action = null;
    public static function action(string $action): string {
        $params = [];
        foreach ((new ReflectionClass(static::class))->getProperties() as $p)
        if($p->getAttributes(Url::class)) $params[$p->name] = $p->getValue();
        $params['_action'] = $action;
        return "@post('".parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH).'?'.http_build_query($params)."')";
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)] final class Url {}
#[Attribute(Attribute::TARGET_PROPERTY)] final class Signal {}
function hydrate(string $class, array $signals): void {
    foreach ((new ReflectionClass($class))->getProperties() as $property) {
        $source = $property->getAttributes(Url::class)
            ? $_GET
            : ($property->getAttributes(Signal::class) ? $signals : []);
        $name = $property->getName();
        if (!array_key_exists($name, $source))
            continue;
        $value = $source[$name];
        $class::${$name} = match ($property->getType()->getName()) {
            'string' => is_string($value) ? $value : null,
            'int'    => filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE),
            'float'  => filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE),
            'bool'   => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'array'  => is_array($value) ? $value : null,
            default  => null,
        } ?? $class::${$name};
    }
}

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/src')) as $f)
    if ($f->isFile() && $f->getExtension() === 'php') require_once $f;
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$class = match ($path) {
    '/' => Home::class,
    default => exit('Not found'),
};

$isDs = isset($_SERVER['HTTP_DATASTAR_REQUEST']);
hydrate($class, $isDs ? json_decode(file_get_contents('php://input'), true) : []);
$class::$_action = $_GET['_action'] ?? null;
ob_start();
$class::render();
$html = ob_get_clean();
if ($isDs) {
    header('Content-Type: text/event-stream');
    echo "event: datastar-patch-elements\n";
    echo 'data: elements ' . str_replace("\n", "\ndata: elements ", $html) . "\n\n";
} else { ?>
    <!doctype html>
    <script type="module" src="https://cdn.jsdelivr.net/gh/starfederation/datastar@v1.0.4/bundles/datastar.js"></script>
    <div>
        <?=$html?>
    </div>
    <?php
}
