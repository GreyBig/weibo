<p align="center"><img src="https://laravel.com/assets/img/components/logo-laravel.svg"></p>

## 一、构建页面 </font>

git checkout -b static-pages 创建一个分支

创建主页、帮助页、关于页的路由，控制器都为StaticPagesController

生成控制器StaticPagesController，用view方法渲染home、help、about的视图

在resources/views的static_pages下新增下面那三个视图

使用通用视图: 把那三个视图的重复代码放入views下的layouts/default.blade.php

最后合并分支   git checkout master    git merge static-pages


## 二、优化页面

git checkout -b filling-layout-style

安装yarn
yarn install --no-bin-links
yarn add cross-env

安装完成之后，让我们对 Laravel 默认生成的 app.scss 文件进行编辑，删除此文件里的所有内容，只留下面一行   // resources/sass/app.scss
// Bootstrap
@import '~bootstrap/scss/bootstrap';

之后就可以用 npm run dev 和 npm run watch-poll了

为应用添加顶部导航，并加入帮助页和登录页的链接。在default.blade.php中
再修改首页信息，多加一些页面元素，样式可以再resources/sass/app.scss中加

页面缓存问题可以在webpack.mix.js中加入.version()来解决

局部视图：把顶部导航和底部从 default 视图中分离出来，成为一个单独的头部视图和底部视图

布局中的链接：
{{ route('help') }}
使用前提是给路由指定了名称

注册页面：创建页面，首先注册路由，生成UserController控制器，写create方法并用view绑定到users.create

创建注册视图，并将首页的注册指向该视图

切换到主分支，如果此时有冲突，用git checkout . 删掉所有修改，
再git merge filling-layout-style


## 三、用户模型

git checkout -b modeling-users
php artisan migrate

将User模型放到Models文件下，并全局替换路径

    $ php artisan tinker
    >>> App\Models\User::create(['name'=> 'Summer','email'=>'summer@example.com','password'=>bcrypt('password')])

User中查找:

    >>> use App\Models\User
    >>> User::find(1)
    
thinker更新用户对象:

    >>> \$user = User::first()
    >>> \$user->update(['name'=>'Summer'])
    

git checkout master
git merge modeling-users


## 四、用户注册

git checkout -b sign-up

#### 定义资源路由 
Route::resource('users', 'UsersController'); (等于下面)
- Route::get('/users', 'UsersController@index')->name('users.index');
- Route::get('/users/create', 'UsersController@create')->name('users.create');
- Route::get('/users/{user}', 'UsersController@show')->name('users.show');
- Route::post('/users', 'UsersController@store')->name('users.store');
- Route::get('/users/{user}/edit', 'UsersController@edit')->name('users.edit');
- Route::patch('/users/{user}', 'UsersController@update')->name('users.update');
- Route::delete('/users/{user}', 'UsersController@destroy')->name('users.destroy');

控制器中创建show方法，此处要用『隐性路由模型绑定』

    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

创建show页面，因为上面用户数据和视图绑定了，视图中可以直接使用 $user 来访问用户实例
    
#### Gavarar头像
User模型中:

    public function gravatar($size = '100')
    {
        $hash = md5(strtolower(trim($this->attributes['email'])));
        return "http://www.gravatar.com/avatar/$hash?s=$size";
    }

全局通用视图resources/views/shared/_user_info.blade.php用于展示用户的头像和用户名等基本信息。
用户个人页面中引入局部视图
还要写样式

#### 注册表单
php artisan migrate:refresh
创建表单页面views/users/create.blade.php
使用bootstrap的card类和form表单写页面,并提交给User控制器的store方法,表单里面加{{ csrf_field() }}
    
    <form method="POST" action="{{ route('users.store')}}"></form>

#### 用户数据验证
User控制器中添加store方法

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:50',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|confirmed|min:6'
        ]);
        return;
    }

#### 注册失败错误消息
Laravel 默认给我们提供了一种非常好的展示错误信息的方法
resources/views/shared/_errors.blade.php

    @if (count($errors) > 0)
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif
用户的注册表单中对该视图进行引用,放在form同级

#### 添加语言包
1. composer require "overtrue/laravel-lang:~3.0"
2. config/app.php中'locale' => 'zh-CN',
<font color=grey size=2>由于该包已经配置了包的自动注册 （Package Auto-Discovery），所以不需要你在配置文件去注册服务提供器即可使用。</font> 

#### 注册成功
逻辑：
1. 将用户提交的信息存储到数据库，并重定向到其个人页面；
2. 在网页顶部位置显示注册成功的提示信息；
   
#### 保护用户并重定向
User控制器中的store方法写入：

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => bcrypt($request->password),
    ]);

    return redirect()->route('users.show', [$user]);

<font color=grey size=2>store 方法接受一个 Illuminate\Http\Request 实例参数，我们可以使用该参数来获得用户的所有输入数据。
如果我们的表单中包含一个 name 字段
这样获取：\$name = $request->name;
获取所有：\$data = $request->all();</font>

#### 消息提示
User控制器中的store方法加一句：

    session()->flash('success', '欢迎，您将在这里开启一段新的旅程~');

消息提醒视图，在views/shared/_messages.blade.php写入：

    @foreach (['danger', 'warning', 'success', 'info'] as $msg)
    @if(session()->has($msg))
        <div class="flash-message">
        <p class="alert alert-{{ $msg }}">
            {{ session()->get($msg) }}
        </p>
        </div>
    @endif
    @endforeach
default.blade.php中引入消息提醒视图。


## 五、会话管理

#### 登录认证流程操作流程
1. 访问登录页面，输入账号密码点击登录；
2. 服务器对用户身份进行认证，认证通过后，记录登录状态并进行页面重定向；
3. 登录成功后的用户，能够使用退出按钮来销毁当前登录状态；
PS：『记住我』功能

#### 会话

    git checkout -b login-logout

#### 会话控制器

    php artisan make:controller SessionsController
路由，包含三个动作
显示登录页面、创建新会话（登录）、销毁会话（退出登录）

    Route::get('login', 'SessionsController@create')->name('login');
    Route::post('login', 'SessionsController@store')->name('login');
    Route::delete('logout', 'SessionsController@destroy')->name('logout');

#### 登录表单
    会话控制器中加入 create 动作，并返回一个指定的登录视图
    新建一个登录视图，并加上表单信息。views/sessions/create.blade.php中

#### 认证用户身份

    public function store(Request $request)
    {
       $credentials = $this->validate($request, [
           'email' => 'required|email|max:255',
           'password' => 'required'
       ]);

       return;
    }

#### Auth认证用户身份和重定向

会话控制其中引入use Auth;
在store方法中写入：

       if (Auth::attempt($credentials)) {
           session()->flash('success', '欢迎回来！');
           return redirect()->route('users.show', [Auth::user()]);
       } else {
           session()->flash('danger', '很抱歉，您的邮箱和密码不匹配');
           return redirect()->back()->withInput();
       }
attempt执行的代码逻辑：

1. 使用 email 字段的值在数据库中查找；
2. 如果用户被找到：
1). 先将传参的 password 值进行哈希加密，然后与数据库中 password 字段中已加密的密码进行匹配；
2). 如果匹配后两个值完全一致，会创建一个『会话』给通过认证的用户。会话在创建的同时，也会种下一个名为 laravel_session 的 HTTP Cookie，以此 Cookie 来记录用户登录状态，最终返回 true；
3). 如果匹配后两个值不一致，则返回 false；
3. 如果用户未找到，则返回 false。
使用 `withInput()` 后模板里 `old('email')` 将能获取到上一次用户提交的内容，这样用户就无需再次输入邮箱等内容：

#### 修改布局中的链接
Laravel 提供了 Auth::check() 方法用于判断当前用户是否已登录，已登录返回 true，未登录返回 false。
layouts/_header.blade.php中

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container ">
        <a class="navbar-brand" href="{{ route('home') }}">Weibo App</a>
        <ul class="navbar-nav justify-content-end">
        @if (Auth::check())
            <li class="nav-item"><a class="nav-link" href="#">用户列表</a></li>
            <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                {{ Auth::user()->name }}
            </a>
            <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                <a class="dropdown-item" href="{{ route('users.show', Auth::user()) }}">个人中心</a>
                <a class="dropdown-item" href="#">编辑资料</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" id="logout" href="#">
                <form action="{{ route('logout') }}" method="POST">
                    {{ csrf_field() }}
                    {{ method_field('DELETE') }}
                    <button class="btn btn-block btn-danger" type="submit" name="button">退出</button>
                </form>
                </a>
            </div>
            </li>
        @else
            <li class="nav-item"><a class="nav-link" href="{{ route('help') }}">帮助</a></li>
            <li class="nav-item" ><a class="nav-link" href="{{ route('login') }}">登录</a></li>

#### 登录认证流程操作流程
1. 访问登录页面，输入账号密码点击登录；
2. 服务器对用户身份进行认证，认证通过后，记录登录状态并进行页面重定向；
3. 登录成功后的用户，能够使用退出按钮来销毁当前登录状态；
PS：『记住我』功能

#### 会话

    git checkout -b login-logout

#### 会话控制器

    php artisan make:controller SessionsController
路由，包含三个动作
显示登录页面、创建新会话（登录）、销毁会话（退出登录）

    Route::get('login', 'SessionsController@create')->name('login');
    Route::post('login', 'SessionsController@store')->name('login');
    Route::delete('logout', 'SessionsController@destroy')->name('logout');

#### 登录表单
    会话控制器中加入 create 动作，并返回一个指定的登录视图
    新建一个登录视图，并加上表单信息。views/sessions/create.blade.php中

#### 认证用户身份

    public function store(Request $request)
    {
       $credentials = $this->validate($request, [
           'email' => 'required|email|max:255',
           'password' => 'required'
       ]);

       return;
    }

#### Auth认证用户身份和重定向

会话控制其中引入use Auth;
在store方法中写入：

       if (Auth::attempt($credentials)) {
           session()->flash('success', '欢迎回来！');
           return redirect()->route('users.show', [Auth::user()]);
       } else {
           session()->flash('danger', '很抱歉，您的邮箱和密码不匹配');
           return redirect()->back()->withInput();
       }
attempt执行的代码逻辑：

1. 使用 email 字段的值在数据库中查找；
2. 如果用户被找到：
1). 先将传参的 password 值进行哈希加密，然后与数据库中 password 字段中已加密的密码进行匹配；
2). 如果匹配后两个值完全一致，会创建一个『会话』给通过认证的用户。会话在创建的同时，也会种下一个名为 laravel_session 的 HTTP Cookie，以此 Cookie 来记录用户登录状态，最终返回 true；
3). 如果匹配后两个值不一致，则返回 false；
3. 如果用户未找到，则返回 false。
使用 `withInput()` 后模板里 `old('email')` 将能获取到上一次用户提交的内容，这样用户就无需再次输入邮箱等内容：


浏览器不支持发送 DELETE 请求，因此我们需要使用一个隐藏域来伪造 DELETE 请求。
Blade 模板中使用 `method_field` 方法来创建隐藏域。
对应的HTML代码：

    <input type="hidden" name="_method" value="DELETE">

这是我们点击用户名，，理应弹出的下拉菜单却没有任何响应。这是因为我们还没有引入 Bootstrap 的 JavaScript 组件库。

layouts/default.blade.php的body中加入

    <script src="{{ mix('js/app.js') }}"></script>

#### 注册后自动登录
对用户控制器的 store 方法进行更改，让用户注册成功后自动登录。
引入Auth;
Auth::login($user);

#### 退出 
Laravel 默认提供的 Auth::logout() 方法来实现用户的退出功能。
会话控制器写入destroy方法：

    public function destroy()
    {
        Auth::logout();
        session()->flash('success', '您已成功退出！');
        return redirect('login');
    }

#### 记住我x
登录视图中加入

    <div class="form-group">
    <div class="form-check">
        <input type="checkbox" class="form-check-input" name="remember" id="exampleCheck1">
        <label class="form-check-label" for="exampleCheck1">记住我</label>
    </div>
    </div>

会话控制器中的 store 方法，为 Auth::attempt() 添加『记住我』参数。

    Auth::attempt($credentials, $request->has('remember'))



## 六、用户CRUD
git checkout -b user-crud

#### 编辑表单
用户控制器上加上编辑用户的操作:

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

views/users/edit.blade.php中

    @extends('layouts.default')
    @section('title', '更新个人资料')

    @section('content')
    <div class="offset-md-2 col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>更新个人资料</h5>
            </div>
            <div class="card-body">
                @include('shared._errors')

                <div class="gravatar_edit">
                    <a href="http://gravatar.com/emails" target="_blank">
                        <img src="{{ $user->gravatar('200') }}" alt="{{ $user->name }}" class="gravatar"/>
                    </a>
                </div>

                <form method="POST" action="{{ route('users.update', $user->id )}}">
                <!-- <form method="POST" action="http://weibo.test/users/1"> -->
                    {{ method_field('PATCH') }}
                    <!-- <input type="hidden" name="_method" value="PATCH"> -->
                    {{ csrf_field() }}

                    <div class="form-group">
                    <label for="name">名称：</label>
                    <input type="text" name="name" class="form-control" value="{{ $user->name }}">
                    </div>

                    <div class="form-group">
                    <label for="email">邮箱：</label>
                    <input type="text" name="email" class="form-control" value="{{ $user->email }}" disabled>
                    </div>

                    <div class="form-group">
                    <label for="password">密码：</label>
                    <input type="password" name="password" class="form-control" value="{{ old('password') }}">
                    </div>

                    <div class="form-group">
                    <label for="password_confirmation">确认密码：</label>
                    <input type="password" name="password_confirmation" class="form-control" value="{{ old('password_confirmation') }}">
                    </div>

                    <button type="submit" class="btn btn-primary">更新</button>
                </form>

            </div>
        </div>
    </div>
    @stop
resources/sass/app.scss中

    .gravatar_edit {
    margin: 15px auto;
    text-align: center;
    .gravatar {
        float: none;
        max-width: 100px;
    }
    }

顶部导航栏的编辑资料加上链接 
`href="{{ route('users.edit', Auth::user()) }}"`

#### 编辑功能
用户控制器写入update方法

    public function update(User $user, Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:50',
            'password' => 'nullable|confirmed|min:6' // nullable: 提供空白密码时也会通过验证
        ]);

        $data = [];
        $data['name'] = $request->name;
        if ($request->password) {
            $data['password'] = bcrypt($request->password);
        }
        $user->update($data);

        session()->flash('success', '个人资料更新成功！');

        return redirect()->route('users.show', $user);
    }

#### 权限系统
现在的应用存在两个巨大的安全隐患：
1. 未登录用户可以访问 edit 和 update 动作
2. 登录用户可以更新其它用户的个人信息

#### 必须先登录
在用户控制器中加上中间件：

    public function __construct()
    {
        $this->middleware('auth', [            
            'except' => ['show', 'create', 'store']
        ]);
    }
__construct 是 PHP 的构造器方法，当一个类对象被创建之前该方法将会被调用。

#### 用户只能编辑自己的资料
当 id 为 1 的用户去尝试更新 id 为 2 的用户信息时，我们应该返回一个 403 禁止访问的异常。
在 Laravel 中可以使用 <font color=#f4645f>授权策略 (Policy)</font> 来对用户的操作权限进行验证
`$ php artisan make:policy UserPolicy`
app/Policies/UserPolicy.php中写入：

    public function update(User $currentUser, User $user)
    {
        return $currentUser->id === $user->id;
    }
app/Providers/AuthServiceProvider.php中将User模型指定授权策略UserPolicy
`\App\Models\User::class  => \App\Policies\UserPolicy::class,`

* 用户控制器中的edit和update方法都加上`$this->authorize('update', $user);`
* 默认的 App\Http\Controllers\Controller 类包含了 Laravel 的 AuthorizesRequests trait
此 trait 提供了 authorize 方法,它可以被用于快速授权一个指定的行为，当无权限运行该行为时会抛出 HttpException。
* 第一个为授权策略的名称，第二个为进行授权验证的数据。
* 这里 update 是指授权类里的 update 授权方法

#### 友好的转向
未登录用户访问编辑页面时，跳转到登录页面，该用户登录成功后跳转到编辑页面。
会话控制器中：

       if (Auth::attempt($credentials, $request->has('remember'))) {
           session()->flash('success', '欢迎回来！');
           $fallback = route('users.show', Auth::user());
           return redirect()->intended($fallback);
       } ...

#### 注册与登录页面访问限制
Auth 中间件提供的 guest 来指定一些只允许未登录用户访问的动作
会话控制器中：

    public function __construct()
    {
        $this->middleware('guest', [
            'only' => ['create']
        ]);
    }

用户控制器中：

        $this->middleware('guest', [
            'only' => ['create']
        ]);

app/Http/Middleware/RedirectIfAuthenticated.php中

        if (Auth::guard($guard)->check()) {
            session()->flash('info', '您已登录，无需再次操作。');
            return redirect('/');
        }

#### 列出所有用户

**用户列表**
用户控制器中的Auth中间件新增index来允许游客访问，并加入index动作

    public function index()
    {
        $users = User::all();
        return view('users.index', compact('users'));
    }
视图，views/users/index.blade.php中：

    @extends('layouts.default')
    @section('title', '所有用户')

    @section('content')
    <div class="offset-md-2 col-md-8">
    <h2 class="mb-4 text-center">所有用户</h2>
    <div class="list-group list-group-flush">
        @foreach ($users as $user)
        <div class="list-group-item">
            <img class="mr-3" src="{{ $user->gravatar() }}" alt="{{ $user->name }}" width=32>
            <a href="{{ route('users.show', $user) }}">
            {{ $user->name }}
            </a>
        </div>
        @endforeach
    </div>
    </div>
    @stop

顶部导航加链接，在_header.blade.php中加入{{ route('users.index') }}
**问题：**
1. 注册用户太少；
2. 用户列表页不支持分页浏览，用户量大的时候会影响性能和用户体验；

#### 示例用户
假数据的生成分为两个阶段：
1. 对要生成假数据的模型指定字段进行赋值 - 『**模型工厂**』；
2. 批量生成假数据模型 - 『**数据填充**』；
#### 模型工厂
Laravel 默认为我们集成了 Faker 扩展包，使用该扩展包可以让我们很方便的生成一些假数据。
在UserFactory.php中添加：

    'created_at' => $date_time,
    'updated_at' => $date_time,
#### 数据填充
`$ php artisan make:seeder UsersTableSeeder`
database/seeds/UsersTableSeeder.php中

        use Illuminate\Database\Seeder;
        use App\Models\User;

        class UsersTableSeeder extends Seeder
        {
            public function run()
            {
                $users = factory(User::class)->times(50)->make();
                User::insert($users->makeVisible(['password', 'remember_token'])->toArray());

                // 对第一位用户的信息进行了更新，方便后面我们使用此账号登录
                $user = User::find(1);
                $user->name = 'Summer';
                $user->email = 'summer@example.com';
                $user->save();
            } 
        }

database/seeds/DatabaseSeeder.php中

    use Illuminate\Database\Seeder;
    use Illuminate\Database\Eloquent\Model;

    class DatabaseSeeder extends Seeder
    {
        public function run()
        {
            Model::unguard();

            $this->call(UsersTableSeeder::class);

            Model::reguard();
        }
    }
`$ php artisan migrate:refresh`
`$ php artisan db:seed --class=UsersTableSeeder` 单独执行，不加就全部

#### 分页

用户控制器中`User::all()`替换成`User::paginate(10)`
用户列表页上渲染分页链接使用 `{!! $users->render() !!}`
index.blade.php中

    <div class="mt-3">
        {!! $users->render() !!}
    </div>

**使用局部视图重构**
将单个用户视图抽离成一个完整的局部视图。局部视图放入users/_user.blade.php

#### 删除用户
* 用户的删除只能通过管理员来操作
* 用户表加上管理员字段用来判定该用户是否为管理员
* 将管理员身份授权给某个指定用户，让其得到删除用户的权限
* 需要在用户列表页面加上删除按钮，只有当我们登录管理员账号时才能看到删除按钮

#### 管理员
`$ php artisan make:migration add_is_admin_to_users_table --table=users`

    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false);
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
`$ php artisan migrate`
UsersTableSeeder.php中将第一个用户设为管理员：
`$user->is_admin = true;`
`$ php artisan migrate:refresh --seed`  // 对数据库进行重置和填充
`$ php artisan tinker`
`>>> App\Models\User::first()`  查看id为1的用户

#### destroy 动作
1. 只有当前登录用户为管理员才能执行删除操作；
2. 删除的用户对象不是自己（即使是管理员也不能自己删自己）。
app/Policies/UserPolicy.php

        public function destroy(User $currentUser, User $user)
        {
            return $currentUser->is_admin && $currentUser->id !== $user->id;
        }
resources/views/users/_user.blade.php中：

    <div class="list-group-item">
        <img class="mr-3" src="{{ $user->gravatar() }}" alt="{{ $user->name }}" width=32>
        <a href="{{ route('users.show', $user) }}">
            {{ $user->name }}
        </a>
        <!-- 管理员才看得到下面删除用户按钮 -->
        @can('destroy', $user) 
            <form action="{{ route('users.destroy', $user->id) }}" method="post" class="float-right">
            {{ csrf_field() }}
            {{ method_field('DELETE') }}
            <button type="submit" class="btn btn-sm btn-danger delete-btn">删除</button>
            </form>
        @endcan
    </div>

为用户控制器添加基本的用户删除动作，
对删除动作加上授权策略，只允许已登录的 **管理员** 进行删除操作。

    public function destroy(User $user)
    {
        // 使用 authorize 方法来对删除操作进行授权验证, destroy在授权策略在Userpolicy中已经定义好
        $this->authorize('destroy', $user);
        $user->delete();
        session()->flash('success', '成功删除用户！');
        return back();
    }

## 七、邮件发送
作用：
* 用户账号激活 —— 用于激活新注册的用户；
* 用户密码重设 —— 帮助用户找回密码；

**账号激活**，整个激活流程如下：
1. 用户注册成功后，自动生成激活令牌；
2. 将激活令牌以链接的形式附带在注册邮件里面，并将邮件发送到用户的注册邮箱上；
3. 用户点击注册链接跳到指定路由，路由收到激活令牌参数后映射给相关控制器动作处理；
4. 控制器拿到激活令牌并进行验证，验证通过后对该用户进行激活，并将其激活状态设置为已激活；
5. 用户激活成功，自动登录；

**密码重设**的步骤如下：
1. 用户点击重设密码链接并跳转到重设密码页面；
2. 在重设密码页面输入邮箱信息并提交；
3. 控制器通过该邮箱查找到指定用户并为该用户生成一个密码令牌，接着将该令牌以链接的形式发送4. 到用户提交的邮箱上；
5. 用户查看自己个人邮箱，点击重置密码链接跳转到重置密码页面；
6. 用户在该页面输入自己的邮箱和密码并提交；
7. 控制器对用户的邮箱和密码重置令牌进行匹配，匹配成功则更新用户密码；

#### 密码重设
Laravel已经有保存密码重置令牌的数据表
有三个字段 email, token, created_at，分别用于生成用户邮箱、密码重置令牌、密码重置令牌的创建时间，并为邮箱和密码重置令牌加上了索引

Laravel 将密码重设功能相关的逻辑代码都放在了 ForgotPasswordController 和 ResetPasswordController 中

routes/web.php

    Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
    Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
    Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
    Route::post('password/reset', 'Auth\ResetPasswordController@reset')->name('password.update');

修改登录页面
resources/views/sessions/create.blade.php

    <label for="password">密码（<a href="{{ route('password.request') }}">忘记密码</a>）：</label>

**重置密码表单**
esources/views/auth/passwords/email.blade.php

    @extends('layouts.default')
    @section('title', '重置密码')

    @section('content')
    <div class="col-md-8 offset-md-2">
    <div class="card ">
        <div class="card-header"><h5>重置密码</h5></div>

        <div class="card-body">
        @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
        @endif

        <form class="" method="POST" action="{{ route('password.email') }}">
            {{ csrf_field() }}

            <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
            <label for="email" class="form-control-label">邮箱地址：</label>

            <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required>

            @if ($errors->has('email'))
                <span class="form-text">
                <strong>{{ $errors->first('email') }}</strong>
                </span>
            @endif
            </div>

            <div class="form-group">
            <button type="submit" class="btn btn-primary">
                发送密码重置邮件
            </button>
            </div>
        </form>
        </div>
    </div>
    </div>
    @endsection

提交成功后，我们可以在 storage/logs/laravel-{today}.log 里找到重置链接发送的邮件 Log

resources/views/auth/passwords/reset.blade.php

    @extends('layouts.default')
    @section('title', '更新密码')

    @section('content')
    <div class="offset-md-1 col-md-10">
    <div class="card">
        <div class="card-header">
            <h5>更新密码</h5>
        </div>

        <div class="card-body">
        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-group row">
            <label for="email" class="col-md-4 col-form-label text-md-right">Email 地址</label>

            <div class="col-md-6">
                <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ $email ?? old('email') }}" required autofocus>

                @if ($errors->has('email'))
                <span class="invalid-feedback" role="alert">
                <strong>{{ $errors->first('email') }}</strong>
                </span>
                @endif
            </div>
            </div>

            <div class="form-group row">
            <label for="password" class="col-md-4 col-form-label text-md-right">密码</label>

            <div class="col-md-6">
                <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>

                @if ($errors->has('password'))
                <span class="invalid-feedback" role="alert">
                <strong>{{ $errors->first('password') }}</strong>
                </span>
                @endif
            </div>
            </div>

            <div class="form-group row">
            <label for="password-confirm" class="col-md-4 col-form-label text-md-right">确认密码</label>

            <div class="col-md-6">
                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required>
            </div>
            </div>

            <div class="form-group row mb-0">
            <div class="col-md-6 offset-md-4">
                <button type="submit" class="btn btn-primary">
                重置密码
                </button>
            </div>
            </div>
        </form>
        </div>
    </div>
    </div>
    @endsection


ResetPasswordController.php将默认重定向地址改为 /