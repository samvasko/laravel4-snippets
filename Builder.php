<?php namespace Bliker;

class Builder
{

    protected $app;
    public $snippets = [];
    public $total;

    public function run()
    {
        // Helps when testing
        if (!is_dir('laravel')) $this->initComposer();

        $this->initLaravel();

        $facades = $this->app['config']['app.aliases'];
        $this->message('Geting the root object behind facades');

        foreach ($facades as $alias => $target)
        {
            $this->digestAlias($alias);
        }

        if (!is_dir('snippets')) \File::makeDirectory('snippets');

        foreach ($this->snippets as $i => $s)
        {
            \File::put('snippets/'.$s->filename(), $s->make());
        }

        $this->app->shutdown();
        echo "-------------------------------------\n";
        echo "     Generated ".$this->total." snippets\n";
        exit(0);
    }

    protected function initComposer()
    {
        $this->message('Downloading laravel');
        $status = system('composer create-project laravel/laravel laravel');
        if ($status != 0) throw new \Exception("\n Error running composer create project \n", 1);
    }

    protected function initLaravel()
    {
        $this->message('Firing up laravel');
        require_once 'laravel/bootstrap/autoload.php';
        $this->app = require_once 'laravel/bootstrap/start.php';
        $this->app->boot();

        // Some classes require Database access, this just sets it to
        // sqlite so no table needs to be present at some mysql somewhere
        \Config::set('database.default', 'sqlite');
    }

    protected function digestAlias($alias)
    {
        printf('%11s', $alias);
        $aliasReflection = new \ReflectionClass($alias);
        if ($aliasReflection->hasMethod('getFacadeRoot'))
        {
            $this->digestFacade($alias, $aliasReflection);
        }
        else
        {
            $this->digestClass($alias, $aliasReflection);
        }
    }

    protected function digestFacade($alias, $aliasReflection)
    {
        // Hi guys!
        $root = call_user_func([$alias, 'getFacadeRoot']);

        // Managers and Database have the real functions hidden there
        if (method_exists($root, 'driver')) $root = $root->driver();
        if (method_exists($root, 'connection')) $root = $root->connection();

        $ref = new \ReflectionClass($root);
        if ($alias == 'DB') {
            $this->factory($alias, $ref, true);
        }
        $count = $this->factory($alias, $aliasReflection);
        $count += $this->factory($alias, $ref);
        // echo " - Total: ".$count." - OK \n";
        printf(" - Total: %2d (Facade)\n", $count);
    }

    // The Special ones that are not inheriting from Illuminate\Support\Facades\Facade
    protected function digestClass($alias, $aliasReflection)
    {
        $count = $this->factory($alias, $aliasReflection, false, true);
        printf(" - Total: %2d (Class Alias)\n", $count);
    }

    protected function message($text)
    {
        echo "\n --- ".$text." \n";
    }

    /**
     * Creates snippets from reference class
     */
    public function factory(
                        $alias,
                        \ReflectionClass $ref,
                        $allowInherited = false,
                        $onlyStatic = false)
    {
        $count = 0;
        foreach ($ref->getMethods() as $i => $meth)
        {
            if (
                // Is callable
                $meth->isPublic() &&

                // not pseudo function like __call
                $meth->name[0] != '_' &&

                // Make sure it is not inherited
                ($allowInherited || $meth->getDeclaringClass()->name == $ref->name) &&

                // Static or not
                (!$onlyStatic || $meth->isStatic()) &&

                // When dealing with methods defined on facades
                $meth->name != 'getFacadeRoot'
            )
            {
                $params = [];
                foreach ($meth->getParameters() as $i => $param) {
                    array_push($params, $param->name);
                }
                $count++;
                $this->total++;
                array_push($this->snippets, new Snippet($alias, $meth->name, $params));
            }
        }

        return $count;
    }
}