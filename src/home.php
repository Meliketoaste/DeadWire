<?php
class Home extends Component {
    #[Url] public static ?int $id = null;
    #[Signal] public static string $title = '';
    public static function render() {
        $todos = &$_SESSION['todos'];
        $todos ??= [];
        match (self::$_action) {
            'add' => $todos[] = ['title' => self::$title, 'done' => false],
            'toggle' => $todos[self::$id]['done'] = !$todos[self::$id]['done'],
            'delete' => $todos = array_diff_key($todos, [self::$id => true]),
            'clear' => $todos = array_filter($todos, fn($t) => !$t['done']),
            default => null,
        };
        ?>
        <section id="app">
            <form data-on:submit="<?=self::action('add')?>">
                <input data-bind:title placeholder="New todo">
                <button>Add</button>
            </form>
            <ul>
                <?php foreach ($todos as $id => $todo): self::$id = $id?>
                <li>
                    <input type="checkbox" <?=$todo['done'] ? 'checked' : ''?> data-on:change="<?=self::action('toggle')?>">
                    <span><?=htmlspecialchars($todo['title'])?></span>
                    <button data-on:click="<?=self::action('delete')?>">Delete</button>
                </li>
                <?php endforeach?>
            </ul>
        </section>
        <?php
    }
}
