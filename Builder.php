<?php namespace Bliker;

class Builder
{

    protected $app;
    public $snippets = [];

    public function run()
    {
        // $this->initComposer();
        $this->initLaravel();

        $facades = $this->app['config']['app.aliases'];
        $this->message('Geting the root object behind facades');

        foreach ($facades as $alias => $target)
        {
            $this->digestAlias($alias);
        }

        // Save
        \File::makeDirectory('snippets');
        foreach ($this->snippets as $i => $s)
        {
            \File::put('snippets/'.$s->filename(), $s->make());
        }

        $this->app->shutdown();
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

        \Config::set('database.default', 'sqlite');
    }

    protected function digestAlias($alias)
    {
        echo $alias;
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
        $this->factory($alias, $aliasReflection);
        $this->factory($alias, $ref);
        echo " - OK \n";
    }

    // The Special ones that are not inheriting from Illuminate\Support\Facades\Facade
    protected function digestClass($alias, $aliasReflection)
    {
        echo " - Not really a facade \n";
        $this->factory($alias, $aliasReflection, false, true);
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
                array_push($this->snippets, new Snippet($alias, $meth->name, $params));
            }
        }
    }
}