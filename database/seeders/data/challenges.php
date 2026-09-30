<?php

/*
| The starter question bank. `answer` is the zero-based index of the correct option.
| ChallengeSeeder turns these into past, today's, scheduled and queued challenges.
*/

return [

    // ───────────────────────────── PHP ─────────────────────────────
    [
        'category' => 'php', 'type' => 'predict_output', 'difficulty' => 'easy',
        'title' => 'Loose vs strict comparison',
        'question' => 'What will this PHP code output?',
        'language' => 'php',
        'code' => <<<'CODE'
$a = "10";
$b = 10;

var_dump($a == $b);
var_dump($a === $b);
CODE,
        'options' => ['bool(true) bool(true)', 'bool(true) bool(false)', 'bool(false) bool(true)', 'bool(false) bool(false)'],
        'answer' => 1,
        'explanation' => '== compares values after type juggling, so the string "10" equals the integer 10. === also compares the type, and a string is never identical to an integer. Default to === and reach for == only when you really want coercion.',
    ],
    [
        'category' => 'php', 'type' => 'find_bug', 'difficulty' => 'medium',
        'title' => 'The lingering reference',
        'question' => 'A teammate expects [20, 40, 60]. What does this actually print?',
        'language' => 'php',
        'code' => <<<'CODE'
$prices = [10, 20, 30];

foreach ($prices as &$price) {
    $price *= 2;
}

foreach ($prices as $price) {
    // just reading…
}

echo implode(', ', $prices);
CODE,
        'options' => ['20, 40, 60', '20, 40, 40', '10, 20, 30', '60, 60, 60'],
        'answer' => 1,
        'explanation' => 'After the first loop $price is still a reference to the last element. The second loop assigns each value into $price, so it keeps overwriting that last element: 20, then 40, then 40 again. Call unset($price) after a by-reference foreach.',
    ],
    [
        'category' => 'php', 'type' => 'predict_output', 'difficulty' => 'medium',
        'title' => '?? versus ?:',
        'question' => 'What does this script echo?',
        'language' => 'php',
        'code' => <<<'CODE'
$count = 0;
$label = '';

echo $count ?? 5;
echo $count ?: 5;
echo $label ?? 'none';
echo $label ?: 'none';
CODE,
        'options' => ['05none', '00none', '55nonenone', '05nonenone'],
        'answer' => 0,
        'explanation' => '?? only falls back when the left side is null or unset, so 0 and \'\' are kept. ?: falls back for anything falsy, so 0 becomes 5 and \'\' becomes "none". Pieces: "0", "5", "", "none".',
    ],
    [
        'category' => 'php', 'type' => 'true_false', 'difficulty' => 'easy',
        'title' => 'Arrays in function calls',
        'question' => 'True or false: by default, PHP passes arrays to functions by value, so changing the array inside the function does not change the caller\'s array.',
        'options' => ['True', 'False'],
        'answer' => 0,
        'explanation' => 'Arrays are value types in PHP. The engine uses copy-on-write, so nothing is actually copied until the function modifies it. Objects behave differently: the handle is copied, so both variables point at the same object.',
    ],
    [
        'category' => 'php', 'type' => 'predict_output', 'difficulty' => 'hard',
        'title' => 'switch vs match',
        'question' => 'Same value, two constructs. What is printed?',
        'language' => 'php',
        'code' => <<<'CODE'
$v = "1";

switch ($v) {
    case 1:   echo "int";    break;
    case "1": echo "string"; break;
}

echo " / ";

echo match ($v) {
    1   => "int",
    "1" => "string",
};
CODE,
        'options' => ['int / int', 'int / string', 'string / string', 'string / int'],
        'answer' => 1,
        'explanation' => 'switch compares loosely (==), so "1" matches case 1 first. match compares strictly (===), so only the string arm matches. match also returns a value and throws UnhandledMatchError when nothing matches.',
    ],
    [
        'category' => 'php', 'type' => 'predict_output', 'difficulty' => 'medium',
        'title' => 'Spaceship sorting',
        'question' => 'What does this print?',
        'language' => 'php',
        'code' => <<<'CODE'
$scores = [3, 10, 1];

usort($scores, fn ($a, $b) => $b <=> $a);

echo implode(',', $scores);
CODE,
        'options' => ['1,3,10', '10,3,1', '3,10,1', '10,1,3'],
        'answer' => 1,
        'explanation' => '<=> returns -1, 0 or 1. Comparing $b to $a (instead of $a to $b) flips the order, giving a descending sort: 10,3,1.',
    ],
    [
        'category' => 'php', 'type' => 'what_would_you_use', 'difficulty' => 'medium',
        'title' => 'Is the key really there?',
        'question' => 'An API payload may contain "email" => null. You need to know whether the key exists at all, even when its value is null. What would you use?',
        'language' => 'php',
        'options' => ["isset(\$data['email'])", "array_key_exists('email', \$data)", "!empty(\$data['email'])", "in_array('email', \$data)"],
        'answer' => 1,
        'explanation' => 'isset() returns false when the value is null, and empty() treats null, "", 0 and "0" as empty. array_key_exists() only asks whether the key is present. in_array() searches values, not keys.',
    ],

    // ─────────────────────────── Laravel ───────────────────────────
    [
        'category' => 'laravel', 'type' => 'what_would_you_use', 'difficulty' => 'easy',
        'title' => '51 queries for 50 posts',
        'question' => 'A page lists 50 posts with each author\'s name, and the debug bar shows 51 queries. What would you use to fix it?',
        'language' => 'php',
        'options' => ["Post::with('author')->get()", 'Post::all()->fresh()', "Post::select('author')->get()", 'Add an index on posts.title'],
        'answer' => 0,
        'explanation' => 'That is the N+1 problem: one query for the posts, then one per post for its author. Eager loading with with(\'author\') fetches all authors in a single extra query, so you end up with 2 queries in total.',
    ],
    [
        'category' => 'laravel', 'type' => 'multiple_choice', 'difficulty' => 'medium',
        'title' => 'firstOrCreate vs firstOrNew',
        'question' => 'Which statement about these two Eloquent methods is correct?',
        'options' => [
            'Both save the model to the database when nothing is found',
            'firstOrCreate saves a new record; firstOrNew returns an unsaved instance',
            'firstOrNew saves a new record; firstOrCreate returns an unsaved instance',
            'Both throw ModelNotFoundException when nothing is found',
        ],
        'answer' => 1,
        'explanation' => 'firstOrCreate() inserts the row straight away. firstOrNew() only builds the model in memory, and you decide whether to call save(). Use firstOrNew when you still need to set more attributes before persisting.',
    ],
    [
        'category' => 'laravel', 'type' => 'predict_output', 'difficulty' => 'medium',
        'title' => 'Collection keys after filter',
        'question' => 'What does this return?',
        'language' => 'php',
        'code' => <<<'CODE'
collect([1, 2, 3, 4])
    ->filter(fn ($n) => $n % 2 === 0)
    ->map(fn ($n) => $n * 10)
    ->all();
CODE,
        'options' => ['[20, 40]', '[1 => 20, 3 => 40]', '[2, 4]', '[10, 20, 30, 40]'],
        'answer' => 1,
        'explanation' => 'filter() keeps the original keys, so the survivors still sit at keys 1 and 3. Serialised to JSON that becomes an object, not an array. Chain ->values() to re-index from 0.',
    ],
    [
        'category' => 'laravel', 'type' => 'what_would_you_use', 'difficulty' => 'medium',
        'title' => '5,000 welcome emails',
        'question' => 'After a CSV import you must send 5,000 welcome emails without making the admin wait on a loading screen. What would you use?',
        'options' => [
            'Queue the mail (Mail::to($user)->queue(...)) and let a worker send it',
            'Call sleep(1) between sends inside the controller',
            'Raise max_execution_time to 10 minutes',
            'Send them from a Blade view composer',
        ],
        'answer' => 0,
        'explanation' => 'Slow, retryable work belongs on a queue. The request returns immediately, a worker sends in the background, and failed jobs can be retried. Raising time limits just makes the request slower and more fragile.',
    ],
    [
        'category' => 'laravel', 'type' => 'multiple_choice', 'difficulty' => 'easy',
        'title' => 'Route model binding miss',
        'question' => 'With the route below, what happens when someone requests /posts/999 and no post has that ID?',
        'language' => 'php',
        'code' => <<<'CODE'
Route::get('/posts/{post}', function (Post $post) {
    return $post;
});
CODE,
        'options' => ['$post is null inside the closure', 'Laravel returns a 404 response', 'Laravel returns a 500 error', 'An empty Post model is injected'],
        'answer' => 1,
        'explanation' => 'Implicit route model binding runs findOrFail-style logic. When the record is missing it throws ModelNotFoundException, which Laravel renders as a 404, so the closure never runs.',
    ],
    [
        'category' => 'laravel', 'type' => 'find_bug', 'difficulty' => 'hard',
        'title' => 'The too-generous model',
        'question' => 'The users table has an is_admin column. What is wrong with this code?',
        'language' => 'php',
        'code' => <<<'CODE'
class User extends Model
{
    protected $guarded = [];
}

// RegisterController
public function store(Request $request)
{
    return User::create($request->all());
}
CODE,
        'options' => [
            'create() does not exist on Eloquent models',
            'Anyone can send is_admin=1 and it will be saved',
            '$guarded must be a string, not an array',
            'The password is never validated as unique',
        ],
        'answer' => 1,
        'explanation' => 'An empty $guarded makes every column mass-assignable, and $request->all() hands over whatever the client sent. That is a mass-assignment vulnerability. Validate the input and pass $request->validated(), and list safe columns in $fillable.',
    ],
    [
        'category' => 'laravel', 'type' => 'true_false', 'difficulty' => 'medium',
        'title' => 'When validation fails',
        'question' => 'True or false: when $request->validate() fails on a normal browser form post, Laravel redirects back to the previous page and flashes the errors and old input to the session.',
        'options' => ['True', 'False'],
        'answer' => 0,
        'explanation' => 'A failed validate() throws ValidationException. For web requests the exception handler redirects back with the errors and old input. For requests that expect JSON it returns a 422 response with the errors instead.',
    ],
    [
        'category' => 'laravel', 'type' => 'multiple_choice', 'difficulty' => 'medium',
        'title' => 'Exception inside a transaction',
        'question' => 'What happens when an exception is thrown inside the closure passed to DB::transaction()?',
        'options' => [
            'The transaction is rolled back and the exception is re-thrown',
            'The transaction commits the queries that already ran',
            'The exception is swallowed and null is returned',
            'The closure is retried forever until it succeeds',
        ],
        'answer' => 0,
        'explanation' => 'DB::transaction() commits only when the closure finishes normally. Any exception rolls everything back and then bubbles up, so your data stays consistent and you still find out something failed.',
    ],

    // ──────────────────────────── MySQL ────────────────────────────
    [
        'category' => 'mysql', 'type' => 'sql', 'difficulty' => 'medium',
        'title' => 'Customers without orders',
        'question' => 'Which query returns the customers who have never placed an order?',
        'language' => 'sql',
        'options' => [
            'SELECT c.* FROM customers c INNER JOIN orders o ON o.customer_id = c.id WHERE o.id IS NULL',
            'SELECT c.* FROM customers c LEFT JOIN orders o ON o.customer_id = c.id WHERE o.id IS NULL',
            'SELECT c.* FROM customers c, orders o WHERE c.id != o.customer_id',
            'SELECT c.* FROM customers c RIGHT JOIN orders o ON o.customer_id = c.id WHERE c.id IS NULL',
        ],
        'answer' => 1,
        'explanation' => 'A LEFT JOIN keeps every customer and fills the order columns with NULL when there is no match, so "o.id IS NULL" isolates customers with no orders. An INNER JOIN drops those customers before the WHERE clause ever sees them.',
    ],
    [
        'category' => 'mysql', 'type' => 'sql', 'difficulty' => 'easy',
        'title' => 'Page three',
        'question' => 'A list shows 10 rows per page. Which clause fetches page 3?',
        'language' => 'sql',
        'options' => ['LIMIT 10 OFFSET 20', 'LIMIT 20 OFFSET 10', 'LIMIT 3, 10', 'LIMIT 30'],
        'answer' => 0,
        'explanation' => 'Page 3 skips the first two pages (2 × 10 = 20 rows) and takes the next 10. The short form "LIMIT 20, 10" means the same: offset first, then row count. Always add ORDER BY, or the pages are not stable.',
    ],
    [
        'category' => 'mysql', 'type' => 'predict_output', 'difficulty' => 'medium',
        'title' => 'NULL equals NULL?',
        'question' => 'What does this query return?',
        'language' => 'sql',
        'code' => 'SELECT NULL = NULL;',
        'options' => ['1', '0', 'NULL', 'A syntax error'],
        'answer' => 2,
        'explanation' => 'NULL means "unknown", and comparing unknown with anything gives unknown. That is why WHERE column = NULL never matches a row. Use IS NULL, IS NOT NULL, or the NULL-safe operator <=>.',
    ],
    [
        'category' => 'mysql', 'type' => 'multiple_choice', 'difficulty' => 'hard',
        'title' => 'Leftmost prefix',
        'question' => 'A table has one composite index on (last_name, first_name). Which query can NOT use that index to find its rows efficiently?',
        'language' => 'sql',
        'options' => [
            "WHERE last_name = 'Shah'",
            "WHERE last_name = 'Shah' AND first_name = 'Ana'",
            "WHERE first_name = 'Ana'",
            "WHERE last_name LIKE 'Sh%'",
        ],
        'answer' => 2,
        'explanation' => 'A composite index is sorted by its first column, then the second within it, like a phone book. You can search by last name, or last name plus first name, but a search on first name alone skips the leftmost column and cannot seek into the index.',
    ],
    [
        'category' => 'mysql', 'type' => 'true_false', 'difficulty' => 'easy',
        'title' => 'Counting NULLs',
        'question' => 'True or false: COUNT(phone) counts every row in the table, including rows where phone is NULL.',
        'options' => ['True', 'False'],
        'answer' => 1,
        'explanation' => 'COUNT(column) counts only the rows where that column is not NULL. COUNT(*) counts all rows. The difference between the two is a quick way to see how many values are missing.',
    ],
    [
        'category' => 'mysql', 'type' => 'multiple_choice', 'difficulty' => 'medium',
        'title' => 'TRUNCATE or DELETE',
        'question' => 'Which statement about TRUNCATE TABLE in MySQL (InnoDB) is true?',
        'options' => [
            'It can be rolled back like any DELETE',
            'It resets AUTO_INCREMENT and causes an implicit commit',
            'It accepts a WHERE clause to remove some rows',
            'It fires the table\'s DELETE triggers for each row',
        ],
        'answer' => 1,
        'explanation' => 'TRUNCATE is DDL: it drops and recreates the table, which resets AUTO_INCREMENT, commits implicitly, takes no WHERE clause and fires no DELETE triggers. DELETE is row-by-row DML that you can filter and roll back.',
    ],

    // ───────────────────────── JavaScript ──────────────────────────
    [
        'category' => 'javascript', 'type' => 'predict_output', 'difficulty' => 'easy',
        'title' => 'typeof surprises',
        'question' => 'What is logged to the console?',
        'language' => 'javascript',
        'code' => 'console.log(typeof null, typeof [], typeof NaN);',
        'options' => ['null array number', 'object object number', 'object array NaN', 'undefined object number'],
        'answer' => 1,
        'explanation' => 'typeof null is "object" (a bug kept since the first version of JavaScript), arrays are objects, and NaN is of type "number". Use Array.isArray() for arrays, value === null for null and Number.isNaN() for NaN.',
    ],
    [
        'category' => 'javascript', 'type' => 'predict_output', 'difficulty' => 'medium',
        'title' => 'Order of the event loop',
        'question' => 'In which order are the letters logged?',
        'language' => 'javascript',
        'code' => <<<'CODE'
console.log('A');

setTimeout(() => console.log('B'), 0);

Promise.resolve().then(() => console.log('C'));

console.log('D');
CODE,
        'options' => ['A B C D', 'A D B C', 'A D C B', 'A C D B'],
        'answer' => 2,
        'explanation' => 'Synchronous code runs first: A, D. Then the microtask queue is drained (the promise callback, C) before the next macrotask (the timer, B) gets a turn, even with a 0 ms delay.',
    ],
    [
        'category' => 'javascript', 'type' => 'find_bug', 'difficulty' => 'medium',
        'title' => 'Three timers, one variable',
        'question' => 'The developer expected 0, 1, 2. What is logged instead?',
        'language' => 'javascript',
        'code' => <<<'CODE'
for (var i = 0; i < 3; i++) {
    setTimeout(() => console.log(i), 100);
}
CODE,
        'options' => ['0 1 2', '3 3 3', '2 2 2', '0 0 0'],
        'answer' => 1,
        'explanation' => 'var is function-scoped, so all three callbacks share one i. By the time they run, the loop has finished and i is 3. Declaring the counter with let creates a fresh binding for every iteration.',
    ],
    [
        'category' => 'javascript', 'type' => 'predict_output', 'difficulty' => 'medium',
        'title' => 'Sorting numbers',
        'question' => 'What is logged?',
        'language' => 'javascript',
        'code' => 'console.log([10, 9, 1].sort());',
        'options' => ['[1, 9, 10]', '[1, 10, 9]', '[10, 9, 1]', '[9, 10, 1]'],
        'answer' => 1,
        'explanation' => 'Without a compare function, sort() converts items to strings and sorts them alphabetically, so "10" comes before "9". For numbers pass a comparator: sort((a, b) => a - b).',
    ],
    [
        'category' => 'javascript', 'type' => 'what_would_you_use', 'difficulty' => 'medium',
        'title' => 'A real copy',
        'question' => 'You need an independent deep copy of an object that contains nested objects and Date values. What would you use?',
        'language' => 'javascript',
        'options' => ['{ ...original }', 'Object.assign({}, original)', 'structuredClone(original)', 'JSON.parse(JSON.stringify(original))'],
        'answer' => 2,
        'explanation' => 'Spread and Object.assign() copy only the top level, so nested objects stay shared. The JSON round trip turns Dates into strings and drops undefined values. structuredClone() makes a true deep copy and keeps Dates, Maps and Sets intact.',
    ],
    [
        'category' => 'javascript', 'type' => 'true_false', 'difficulty' => 'easy',
        'title' => 'What const protects',
        'question' => 'True or false: declaring an object with const makes it immutable, so its properties can no longer be changed.',
        'options' => ['True', 'False'],
        'answer' => 1,
        'explanation' => 'const only prevents reassigning the variable. The object it points to can still be mutated: user.name = "x" works fine. Use Object.freeze() for a shallow freeze.',
    ],

    // ───────────────────────────── Vue ─────────────────────────────
    [
        'category' => 'vue', 'type' => 'multiple_choice', 'difficulty' => 'easy',
        'title' => 'Reading a ref',
        'question' => 'Inside <script setup>, how do you read the current number from this ref?',
        'language' => 'javascript',
        'code' => 'const count = ref(0);',
        'options' => ['count', 'count.value', 'count()', 'count.get()'],
        'answer' => 1,
        'explanation' => 'A ref is a wrapper object and the actual value lives on .value. Templates unwrap refs automatically, which is why you write {{ count }} there, but in script code you always need count.value.',
    ],
    [
        'category' => 'vue', 'type' => 'find_bug', 'difficulty' => 'medium',
        'title' => 'The counter that never moves',
        'question' => 'The template shows {{ state.count }} and it stays at 0 no matter how often increment() runs. Why?',
        'language' => 'javascript',
        'code' => <<<'CODE'
const state = reactive({ count: 0 });

let { count } = state;

const increment = () => count++;
CODE,
        'options' => [
            'reactive() only works with arrays',
            'Destructuring copied the number, so count++ changes a plain local variable',
            'Arrow functions cannot change reactive state',
            'count must be declared with const',
        ],
        'answer' => 1,
        'explanation' => 'Destructuring a reactive object copies primitive values out of it and the connection is lost. Mutate state.count++ directly, or use toRefs(state) to get refs that stay linked to the source.',
    ],
    [
        'category' => 'vue', 'type' => 'what_would_you_use', 'difficulty' => 'easy',
        'title' => 'Child talks to parent',
        'question' => 'A child form component needs to tell its parent that the form was saved. What would you use?',
        'options' => ['Mutate a prop inside the child', 'Emit an event with defineEmits', 'Reach up through $parent', 'Write to a global window variable'],
        'answer' => 1,
        'explanation' => 'Data flows down through props and messages flow up through events. defineEmits keeps the child reusable and makes the contract explicit. Props are read-only, and $parent couples the child to one specific parent.',
    ],
    [
        'category' => 'vue', 'type' => 'what_would_you_use', 'difficulty' => 'medium',
        'title' => 'Derived and cached',
        'question' => 'fullName should always equal firstName + " " + lastName and must not be recalculated on every render. What would you use?',
        'options' => ['A method called from the template', 'A computed property', 'A watcher that writes to a third ref', 'A lifecycle hook'],
        'answer' => 1,
        'explanation' => 'computed() caches its result and only recalculates when one of its reactive dependencies changes. A method reruns on every render, and a watcher is meant for side effects such as API calls, not for deriving state.',
    ],
    [
        'category' => 'vue', 'type' => 'true_false', 'difficulty' => 'medium',
        'title' => 'v-if and v-show',
        'question' => 'True or false: v-if and v-show both remove the element from the DOM when the condition is false.',
        'options' => ['True', 'False'],
        'answer' => 1,
        'explanation' => 'v-if really adds and removes the element, and destroys child components. v-show always renders it and just toggles display: none. Use v-show for things that toggle often and v-if when the condition rarely changes.',
    ],

    // ───────────────────────────── Git ─────────────────────────────
    [
        'category' => 'git', 'type' => 'what_would_you_use', 'difficulty' => 'easy',
        'title' => 'Undo the commit, keep the work',
        'question' => 'You just committed locally (not pushed) and want to undo that commit while keeping all its changes staged. What would you use?',
        'language' => 'bash',
        'options' => ['git reset --hard HEAD~1', 'git reset --soft HEAD~1', 'git revert HEAD', 'git checkout HEAD~1'],
        'answer' => 1,
        'explanation' => '--soft moves the branch pointer back one commit and leaves the index and working tree alone, so the changes stay staged. --hard would throw the changes away, and revert creates a new commit instead of removing one.',
    ],
    [
        'category' => 'git', 'type' => 'multiple_choice', 'difficulty' => 'medium',
        'title' => 'What rebase really does',
        'question' => 'You are on a feature branch and run "git rebase main". What happens?',
        'options' => [
            'main is merged into the feature branch with a merge commit',
            'Your feature commits are replayed on top of main and get new hashes',
            'main is reset to match your feature branch',
            'Your feature commits are squashed into a single commit',
        ],
        'answer' => 1,
        'explanation' => 'Rebase takes your commits off, moves the branch to the tip of main and re-applies them one by one. The result is a straight history, but the commits are rewritten, so do not rebase commits other people already pulled.',
    ],
    [
        'category' => 'git', 'type' => 'what_would_you_use', 'difficulty' => 'medium',
        'title' => 'Bad commit on a shared branch',
        'question' => 'A broken commit is already pushed to a branch your whole team uses. How do you undo it safely?',
        'language' => 'bash',
        'options' => ['git reset --hard HEAD~1 && git push --force', 'git revert <commit>', 'git commit --amend', 'git stash'],
        'answer' => 1,
        'explanation' => 'revert adds a new commit that applies the opposite change, so history stays intact and nobody\'s local branch breaks. Resetting and force-pushing rewrites history that teammates already have.',
    ],
    [
        'category' => 'git', 'type' => 'what_would_you_use', 'difficulty' => 'easy',
        'title' => 'Just that one commit',
        'question' => 'A hotfix commit lives on another branch and you need only that commit on yours. What would you use?',
        'language' => 'bash',
        'options' => ['git merge <branch>', 'git cherry-pick <commit>', 'git rebase <branch>', 'git pull --all'],
        'answer' => 1,
        'explanation' => 'cherry-pick copies the change of one specific commit onto your current branch as a new commit. merge and rebase would bring over everything else from that branch too.',
    ],
    [
        'category' => 'git', 'type' => 'true_false', 'difficulty' => 'medium',
        'title' => 'fetch and your files',
        'question' => 'True or false: git fetch updates the files in your working directory.',
        'options' => ['True', 'False'],
        'answer' => 1,
        'explanation' => 'fetch only downloads new commits and updates the remote-tracking branches such as origin/main. Your own branch and files stay untouched until you merge or rebase. git pull is fetch followed by a merge (or rebase).',
    ],
    [
        'category' => 'git', 'type' => 'what_would_you_use', 'difficulty' => 'hard',
        'title' => 'Commits lost after a hard reset',
        'question' => 'You ran git reset --hard and wiped out two commits you still need. Where do you look to get them back?',
        'language' => 'bash',
        'options' => ['git log', 'git reflog', 'git status', 'git diff'],
        'answer' => 1,
        'explanation' => 'The reflog records every position HEAD has been at, including commits no branch points to any more. Find the hash there and run git reset --hard <hash> or git cherry-pick <hash>. Unreferenced commits are kept for about 30 days by default.',
    ],

    // ──────────────────────────── Linux ────────────────────────────
    [
        'category' => 'linux', 'type' => 'multiple_choice', 'difficulty' => 'easy',
        'title' => 'Reading chmod 644',
        'question' => 'What permissions does "chmod 644 notes.txt" set?',
        'options' => [
            'Owner: read + write · Group: read · Others: read',
            'Owner: read + write + execute · Group: read · Others: read',
            'Owner: read + write · Group: read + write · Others: read',
            'Everyone: read + write',
        ],
        'answer' => 0,
        'explanation' => 'Each digit is a sum of read (4), write (2) and execute (1), in the order owner, group, others. 6 = 4 + 2 (read and write) and 4 = read only.',
    ],
    [
        'category' => 'linux', 'type' => 'what_would_you_use', 'difficulty' => 'easy',
        'title' => 'Watch the log live',
        'question' => 'You want to watch new lines appear in storage/logs/laravel.log while you reproduce a bug. What would you use?',
        'language' => 'bash',
        'options' => ['cat storage/logs/laravel.log', 'tail -f storage/logs/laravel.log', 'head storage/logs/laravel.log', 'touch storage/logs/laravel.log'],
        'answer' => 1,
        'explanation' => 'tail -f prints the end of the file and keeps following it as new lines are written. cat dumps the whole file once, and head shows only the beginning.',
    ],
    [
        'category' => 'linux', 'type' => 'multiple_choice', 'difficulty' => 'medium',
        'title' => 'Decoding 2>&1',
        'question' => 'In "php artisan queue:work > worker.log 2>&1", what does 2>&1 do?',
        'options' => [
            'Runs the command twice',
            'Sends error output (stderr) to the same place as normal output (stdout)',
            'Runs the command in the background',
            'Writes to a file called 1',
        ],
        'answer' => 1,
        'explanation' => 'File descriptor 1 is stdout and 2 is stderr. "2>&1" points stderr at wherever stdout currently goes, which here is worker.log, so errors and normal output land in the same file.',
    ],
    [
        'category' => 'linux', 'type' => 'what_would_you_use', 'difficulty' => 'medium',
        'title' => 'Who is on port 3306?',
        'question' => 'MySQL will not start because port 3306 is already in use. How do you find the process holding it?',
        'language' => 'bash',
        'options' => ['lsof -i :3306', 'ps 3306', 'top -p 3306', 'kill -l 3306'],
        'answer' => 0,
        'explanation' => 'lsof -i :3306 lists the process that has that port open, with its PID. On Linux "ss -ltnp" gives the same information. ps and top expect a process ID, not a port.',
    ],
    [
        'category' => 'linux', 'type' => 'true_false', 'difficulty' => 'easy',
        'title' => 'The gentle kill',
        'question' => 'True or false: kill -9 gives a process the chance to clean up (close files, finish a write) before it exits.',
        'options' => ['True', 'False'],
        'answer' => 1,
        'explanation' => 'Signal 9 (SIGKILL) cannot be caught or ignored, so the process is stopped immediately with no cleanup. Try a plain kill first: it sends SIGTERM (15), which lets the process shut down gracefully.',
    ],

    // ────────────────────────── HTML / CSS ─────────────────────────
    [
        'category' => 'html-css', 'type' => 'predict_output', 'difficulty' => 'easy',
        'title' => 'Which colour wins?',
        'question' => 'The markup is <div class="card"><h1 id="title" class="title">Hi</h1></div>. What colour is the heading?',
        'language' => 'css',
        'code' => <<<'CODE'
h1.title      { color: blue; }
.card .title  { color: green; }
#title        { color: red; }
CODE,
        'options' => ['Blue', 'Green', 'Red', 'Black (the browser default)'],
        'answer' => 2,
        'explanation' => 'Specificity is compared as (IDs, classes, elements). #title scores (1,0,0) and beats .card .title at (0,2,0) and h1.title at (0,1,1), regardless of source order.',
    ],
    [
        'category' => 'html-css', 'type' => 'what_would_you_use', 'difficulty' => 'easy',
        'title' => 'Dead centre',
        'question' => 'You want a child perfectly centred, horizontally and vertically, inside its parent. Which rules on the parent do it?',
        'language' => 'css',
        'options' => [
            'display: grid; place-items: center;',
            'text-align: center; vertical-align: middle;',
            'float: center;',
            'margin: center;',
        ],
        'answer' => 0,
        'explanation' => 'place-items: center is shorthand for align-items and justify-items, and centres the child on both axes. The flexbox version is display: flex; justify-content: center; align-items: center. "float: center" and "margin: center" are not valid CSS.',
    ],
    [
        'category' => 'html-css', 'type' => 'multiple_choice', 'difficulty' => 'medium',
        'title' => 'How wide is the box?',
        'question' => 'How much horizontal space does this element take up (ignoring margins)?',
        'language' => 'css',
        'code' => <<<'CODE'
.panel {
    box-sizing: border-box;
    width: 200px;
    padding: 20px;
    border: 5px solid;
}
CODE,
        'options' => ['200px', '240px', '250px', '150px'],
        'answer' => 0,
        'explanation' => 'With border-box, padding and border are included in the width, so the box stays 200px and the content area shrinks to 150px. With the default content-box it would be 200 + 40 + 10 = 250px.',
    ],
    [
        'category' => 'html-css', 'type' => 'true_false', 'difficulty' => 'medium',
        'title' => 'The accidental submit',
        'question' => 'True or false: a <button> inside a <form> with no type attribute submits the form when clicked.',
        'options' => ['True', 'False'],
        'answer' => 0,
        'explanation' => 'The default type of a button is "submit". That is why a "Cancel" or "Add row" button inside a form unexpectedly submits it. Give those buttons type="button".',
    ],
    [
        'category' => 'html-css', 'type' => 'what_would_you_use', 'difficulty' => 'medium',
        'title' => 'Labelling an input',
        'question' => 'Which is the most accessible way to give a text input a visible name?',
        'options' => [
            'A <label> whose for attribute matches the input\'s id',
            'Placeholder text only',
            'A <span> placed just before the input',
            'A title attribute on the input',
        ],
        'answer' => 0,
        'explanation' => 'A linked <label> is announced by screen readers and also makes the text clickable to focus the field. A placeholder disappears as soon as the user types, and a plain span has no programmatic link to the input.',
    ],

    // ────────────────────────── Debugging ──────────────────────────
    [
        'category' => 'debugging', 'type' => 'find_bug', 'difficulty' => 'easy',
        'title' => 'One step too far',
        'question' => 'This loop prints abc and then a warning. What is the bug?',
        'language' => 'php',
        'code' => <<<'CODE'
$items = ['a', 'b', 'c'];

for ($i = 0; $i <= count($items); $i++) {
    echo $items[$i];
}
CODE,
        'options' => [
            'The loop should start at 1',
            'The condition should be < instead of <=',
            'count() cannot be used in a loop condition',
            'echo cannot print array elements',
        ],
        'answer' => 1,
        'explanation' => 'Indexes run from 0 to count - 1. With <= the loop runs one extra time and reads $items[3], which does not exist: "Undefined array key 3". A foreach avoids this whole class of off-by-one bug.',
    ],
    [
        'category' => 'debugging', 'type' => 'find_bug', 'difficulty' => 'medium',
        'title' => 'Saved too soon',
        'question' => '"All saved!" appears before the records are actually saved. Why?',
        'language' => 'javascript',
        'code' => <<<'CODE'
const ids = [1, 2, 3];

ids.forEach(async (id) => {
    await save(id);
});

console.log('All saved!');
CODE,
        'options' => [
            'forEach does not wait for async callbacks',
            'await only works inside try/catch',
            'save() must be called with new',
            'Arrays of numbers cannot be iterated with forEach',
        ],
        'answer' => 0,
        'explanation' => 'forEach ignores the promises its callback returns, so it finishes immediately. Use "for (const id of ids) { await save(id); }" for sequential saves, or "await Promise.all(ids.map(save))" to run them in parallel.',
    ],
    [
        'category' => 'debugging', 'type' => 'find_bug', 'difficulty' => 'hard',
        'title' => 'Every email is taken',
        'question' => 'The users table is completely empty, yet this check reports that the email is already taken. Why?',
        'language' => 'php',
        'code' => <<<'CODE'
$user = User::where('email', $email)->get();

if ($user) {
    return 'That email is already taken.';
}
CODE,
        'options' => [
            'where() needs three arguments',
            'get() returns a Collection, and even an empty Collection is truthy',
            'Eloquent caches the result of the previous query',
            '$email has to be cast to a string first',
        ],
        'answer' => 1,
        'explanation' => 'get() always returns a Collection object, and in PHP every object is truthy, even a collection with zero items. Use ->exists() for a yes/no check, or ->first(), which returns null when nothing matches.',
    ],
    [
        'category' => 'debugging', 'type' => 'find_bug', 'difficulty' => 'easy',
        'title' => 'Everyone is an admin',
        'question' => 'This function returns true for every user. What is the bug?',
        'language' => 'php',
        'code' => <<<'CODE'
function isAdmin(array $user): bool
{
    if ($user['role'] = 'admin') {
        return true;
    }

    return false;
}
CODE,
        'options' => [
            'Arrays cannot be type-hinted',
            'A single = assigns "admin" instead of comparing',
            'The function needs an else branch',
            'The string should use double quotes',
        ],
        'answer' => 1,
        'explanation' => 'A single = assigns the value, and the assigned string "admin" is truthy, so the condition always passes. Compare with ===. The whole function can be one line: return $user[\'role\'] === \'admin\';',
    ],
    [
        'category' => 'debugging', 'type' => 'find_bug', 'difficulty' => 'medium',
        'title' => 'Invalid use of group function',
        'question' => 'MySQL rejects this query. What is the fix?',
        'language' => 'sql',
        'code' => <<<'CODE'
SELECT customer_id, COUNT(*) AS orders
FROM orders
WHERE COUNT(*) > 5
GROUP BY customer_id;
CODE,
        'options' => [
            'Move the condition to HAVING COUNT(*) > 5 after GROUP BY',
            'Put GROUP BY before FROM',
            'Replace COUNT(*) with SUM(*)',
            'Add DISTINCT after SELECT',
        ],
        'answer' => 0,
        'explanation' => 'WHERE filters single rows before they are grouped, so aggregates do not exist yet at that point. HAVING filters the groups after aggregation, which is exactly where a condition on COUNT(*) belongs.',
    ],

    // ──────────────────── General programming ──────────────────────
    [
        'category' => 'general', 'type' => 'predict_output', 'difficulty' => 'easy',
        'title' => 'Floating point maths',
        'question' => 'What does this print? (JavaScript, Python and most other languages behave the same way.)',
        'language' => 'php',
        'code' => <<<'CODE'
if (0.1 + 0.2 == 0.3) {
    echo 'equal';
} else {
    echo 'not equal';
}
CODE,
        'options' => ['equal', 'not equal', 'A type error', 'Nothing'],
        'answer' => 1,
        'explanation' => '0.1 and 0.2 cannot be represented exactly in binary floating point, so their sum is 0.30000000000000004. Compare floats with a small tolerance, and store money as integer cents or a decimal type.',
    ],
    [
        'category' => 'general', 'type' => 'multiple_choice', 'difficulty' => 'easy',
        'title' => 'Cost of a binary search',
        'question' => 'What is the time complexity of binary search on a sorted array of n items?',
        'options' => ['O(1)', 'O(log n)', 'O(n)', 'O(n log n)'],
        'answer' => 1,
        'explanation' => 'Each comparison discards half of the remaining items, so a million sorted items need only about 20 steps. The array must already be sorted for this to work.',
    ],
    [
        'category' => 'general', 'type' => 'multiple_choice', 'difficulty' => 'medium',
        'title' => 'Idempotent requests',
        'question' => 'What does it mean for an HTTP method to be idempotent?',
        'options' => [
            'It never changes anything on the server',
            'Sending the same request many times leaves the server in the same state as sending it once',
            'It always returns the same status code',
            'It can only be called by authenticated users',
        ],
        'answer' => 1,
        'explanation' => 'GET, PUT and DELETE are idempotent: repeating them does not add further changes. POST is not, which is why a retried POST can create duplicates unless you protect it with an idempotency key.',
    ],
    [
        'category' => 'general', 'type' => 'what_would_you_use', 'difficulty' => 'medium',
        'title' => 'A million lookups',
        'question' => 'You must check thousands of times per second whether an ID is in a collection of one million IDs. Which structure would you use?',
        'options' => ['An unsorted array, scanned each time', 'A hash set / hash map keyed by ID', 'A linked list', 'A comma-separated string'],
        'answer' => 1,
        'explanation' => 'A hash set answers "is it in there?" in O(1) on average. Scanning an array or list is O(n), which is up to a million comparisons per lookup. In PHP, isset($set[$id]) on an array keyed by ID gives you exactly this.',
    ],
    [
        'category' => 'general', 'type' => 'true_false', 'difficulty' => 'easy',
        'title' => 'How a stack works',
        'question' => 'True or false: a stack is a first-in, first-out (FIFO) data structure.',
        'options' => ['True', 'False'],
        'answer' => 1,
        'explanation' => 'A stack is last-in, first-out (LIFO): the last item pushed is the first one popped, like a pile of plates. A queue is FIFO. The call stack and undo history are everyday stacks.',
    ],
    [
        'category' => 'general', 'type' => 'multiple_choice', 'difficulty' => 'medium',
        'title' => 'One class, three jobs',
        'question' => 'An OrderManager class validates input, writes to the database and sends the confirmation email. Which SOLID principle does it break most clearly?',
        'options' => ['Single Responsibility', 'Liskov Substitution', 'Interface Segregation', 'Dependency Inversion'],
        'answer' => 0,
        'explanation' => 'The Single Responsibility Principle says a class should have one reason to change. Here a change to validation, storage or email wording all touch the same class. Splitting them makes each part easier to test and reuse.',
    ],
];
