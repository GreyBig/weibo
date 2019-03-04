<p align="center"><img src="https://laravel.com/assets/img/components/logo-laravel.svg"></p>

## 一、构建页面

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
    
    <form method="POST" action="{{ route('users.store')}}">

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
   
**保护用户并重定向**
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

**消息提示**
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

**登录认证流程操作流程**
1. 访问登录页面，输入账号密码点击登录；
2. 服务器对用户身份进行认证，认证通过后，记录登录状态并进行页面重定向；
3. 登录成功后的用户，能够使用退出按钮来销毁当前登录状态；
PS：『记住我』功能

#### 会话

    git checkout -b login-logout

**会话控制器:**

    php artisan make:controller SessionsController
路由，包含三个动作
显示登录页面、创建新会话（登录）、销毁会话（退出登录）

    Route::get('login', 'SessionsController@create')->name('login');
    Route::post('login', 'SessionsController@store')->name('login');
    Route::delete('logout', 'SessionsController@destroy')->name('logout');

**登录表单：**
    会话控制器中加入 create 动作，并返回一个指定的登录视图
    新建一个登录视图，并加上表单信息。views/sessions/create.blade.php中

**认证用户身份**

    public function store(Request $request)
    {
       $credentials = $this->validate($request, [
           'email' => 'required|email|max:255',
           'password' => 'required'
       ]);

       return;
    }

**Auth认证用户身份和重定向**

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

**修改布局中的链接**
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
        @endif
        </ul>
    </div>
    </nav>

浏览器不支持发送 DELETE 请求，因此我们需要使用一个隐藏域来伪造 DELETE 请求。
Blade 模板中使用 `method_field` 方法来创建隐藏域。
对应的HTML代码：

    <input type="hidden" name="_method" value="DELETE">

这是我们点击用户名，，理应弹出的下拉菜单却没有任何响应。这是因为我们还没有引入 Bootstrap 的 JavaScript 组件库。

layouts/default.blade.php的body中加入

    <script src="{{ mix('js/app.js') }}"></script>

**注册后自动登录**
对用户控制器的 store 方法进行更改，让用户注册成功后自动登录。
引入Auth;
Auth::login($user);

**退出**
Laravel 默认提供的 Auth::logout() 方法来实现用户的退出功能。
会话控制器写入destroy方法：

    public function destroy()
    {
        Auth::logout();
        session()->flash('success', '您已成功退出！');
        return redirect('login');
    }

**记住我**
登录视图中加入

    <div class="form-group">
    <div class="form-check">
        <input type="checkbox" class="form-check-input" name="remember" id="exampleCheck1">
        <label class="form-check-label" for="exampleCheck1">记住我</label>
    </div>
    </div>

会话控制器中的 store 方法，为 Auth::attempt() 添加『记住我』参数。

    Auth::attempt($credentials, $request->has('remember'))