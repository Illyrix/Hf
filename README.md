# Hf Framework
A light PHP framework, supports RESTful development.

## Requirement
PHP 5.6.12 is recommended, even it works on 5.6.  
NOTICE: NOT sure it would work on PHP version less than 5.6.

If you enable composer support in configure file, `composer.json` and `vendor/autoload.php` is necessary.

## License
Hf Framework uses [Apache License 2.0](http://www.apache.org/licenses/LICENSE-2.0).
## Document
* [English version](#EN)
* [中文版本](#CN)

### English version
<a name="EN"></a>

### 中文版本
<a name="CN"></a>
>Hf Framework 是基于 MVC 架构的框架.  
关于 MVC 架构的基础知识就不在此赘述. 如果你对这方面没有接触过, 网络上有很多相关的不错的教程.

* 控制器

 1. 控制器的创建  
通常来说, 项目文件的控制器放在 `app/controller` 目录下.  
控制器文件的文件名应该以大写字母开头, 且与类名保持一致. 后缀名是 `.php` .
例如在 `app/controller` 下有一个 `Index.php`, 其中内容应该是:
```php
    <?php

    namespace app\controller;

    use core\system\Controller;

    class Index extends Controller
    {
        public function index() {
            echo "Hello World";
        }
    }
```
新创建的控制器的命名空间应该是 `app\controller`, 与文件目录保持一致. 所有控制器应该继承自 `core\system\Controller` 类.  
__注意: 所有控制器的类名和文件名均应该大写首字母__  
现在访问 `Server/index.php/Index/index`, 如果配置没有出错的话页面会显示 `Hello World`.
URL 的第一段(`Index`)是用于确定需要调用的控制器, 而第二段(`index`)是用于确定需要调用的方法.  
在 URL 没有第一段的情况下(`Server/index.php`), 会默认访问 `Index` 控制器下的 `index` 方法.  
或者没有第二段时(`Sever/index.php/Index`), 会默认访问 `index` 方法.
>默认访问的控制器和方法可以通过 `'DEFAULT_ROUTE_CONTROLLER'` 和 `'DEFAULT_ROUTE_METHOD'` 进行设置.

 2. 获取输入  
虽然在控制器里面也可以通过 `$\_GET` 的传统方式来访问输入变量, 但 Hf 提供了 Input 类使输入的获取变得更安全和方便.  
在控制器方法里面通过访问 `$this->input` 返回的就是 Input 类的一个实例.  
Input 类提供了以下方法:
   * ::input()
   * ::get()
   * ::post()
   * ::cookie()

   这四个方法均接受四个参数:
   * $filed = "" 参数名.如果有 html 特殊字符要进行 `htmlspecialchars` 转义.如果为空则是返回整个输入数组.
   * $default = null 如果不存在值则作为返回值.
   * $filter = "" 过滤器函数, 是一个匿名函数或者类似 'htmlspecialchars' 函数名的字符串.为空则默认 `htmlspecialchars`.
   * $param = Array() 过滤器函数的额外参数数组.

   例如在控制器中:  
    `$this->input->get('id', 1, 'intval');`  
   返回通过 GET 方式获取的 id 的参数值. 默认为1,且对接收的值强制转换成 int.(除URL中第一第二段以外, 每两个段将组合成奇数段为键,偶数段为值的键值对合并到 $\_GET 数组中去.)  
   又比如:  
    `$this->input->input('name');`  
   返回在3种输入方式合并后的数组进行查询字段为 `'name'` 的结果.

 3. 前置和后置操作  
 在控制器类中实现 `_preAction()` 方法就可以在控制器被实例化的时候调用, 注意这是在调用相应方法进行逻辑处理之前.
 同理实现 `_postAction()` 方法就可以在逻辑处理之后进行调用.  
 __注意:建议这两个方法声明为 `protected` 或者 `private`__
 4. 构造函数  
 如果这个控制器有构造函数的话, 务必在构造函数中调用基类的构造函数.
 ```php
 public function __construct() {
        parent::__construct();
        //...
    }
 ```

* 视图  
在 Hf 中使用的是 Twig 模板引擎.  
所有模板放在 `app/view/{Controller}` 目录下. 通过在控制器中访问 `$this->view` 返回 View 类. View 类提供以下方法:
 * ::render()  
   绑定输出变量  
   参数:
   * $index string|int  
   * $value mixed  
 * ::display()  
   渲染模板  
   参数:
      * $view_name string  
 * ::fetch()  
   渲染模板但不输出, 返回渲染的页面  
   参数:
      * $view_name string  
 * ::twig()  
   直接返回 Twig 对象, 以实现复杂的操作.  

  例如在 `app/controller/Index.php` 中的 `Index` 控制器实现:
```php
$this->view->render('title', 'Hello');
$this->view->display('index');
```
这样绑定参数之后就会进行 `app/view/Index/index.html` 的渲染(默认配置情况下).
>关于 Twig 模板引擎的使用请参阅 [twig官网](http://twig.sensiolabs.org/).

* 模型  
所有模型类应该放在 `app/model` 目录下, 命名规则与控制器相同. 一个最简单的模型实例如下:  
```php
<?php
    namespace app\model;

    use core\system\Model;

    class User extends Model {

    }
```

  在这个例子中, 我们创建了一个叫 User 的模型, 并让它继承于 `core\system\Model`. 这样就完成了一个模型类的创建.
在控制器中 `new` 相应的类即可完成模型的实例化, 并在控制器中使用.
模型类提供如下方法完成对数据库的操作:
 * ::query($str)  
   直接将参数字符串作为查询语句返回查询结果.
 * ::getLastQuery()  
   返回执行的最后一条 SQL 语句.
 * ::select()  
   组织 SQL 查询语句并返回其结果.
 * ::where($arr)
   设定查询的 WHERE 条件.通常用于连贯操作:  
   `$model->where(['id'=>5])->select()`
 * ::insert($arr)
   往数据库插入新的记录.  
   `$modle->insert(['name'=>'ha', 'gender'=>1])`
 * ::delete()  
   删除数据.  
   `$model->where(['id'=>1])->delete()`
 * ::count()
   计算查询结果的个数.  
   `$model->where(['gender'=>1])->count()`
 * ::update($arr)
   更新记录.  
   `$model->update(['id'=>5, 'name'=>'Bob'])`
 * ::field()
   选取特定的列.  
   `$model->where(['name'=>'Alice'])->field('id')->select()`
 * ::limit($int)
   限制查询的范围.  
   `$model->where(['gender'=>0])->limit(5)->select()`
 * ::order($str)
   选择排序方式.  
   `$model->where(['gender'=>0])->order('id DESC')->select()`
 * ::beginTransaction()
   开始事务.  
   `$model->beginTransaction()`
 * ::commit()
   提交事务.  
   `$model->commit()`
 * ::rollBack()
   回滚事务.  
   `$model->rollBack()`
 * ::inTransaction()
   是否处在一个事务中.  
   `$model->inTransaction()`

  >注意:
>1. 暂时未实现 sum, max 等函数.
>2. 更新记录需要数据中有主键.
>3. 删除操作默认执行软删除, 即只更新 `deletetime` 字段. 使用 `delete(true)` 来执行硬删除.

  __目前数据库的驱动只支持 MySQL.__
* 路由  
  通常情况下, 所有路由的配置应该放在 `app/route` 目录下. 至于文件名没有特殊要求.
  ```php
  <?php
  use core\system\Route;
  Route::route('id/{id}', 'Index@index');
  Route::filter('id', 'intval');
  ```
  当访问 `server/index.php/id/5`时, 此路由就会被匹配, 在把 `['id'=>5]` 合并入 `$\_GET` 之后调用 `Index` 控制器下的 `index` 方法.  
  __注意: `Route::filter()` 方法是在路由匹配之后再进行的过滤, 并不影响路由的匹配__  
  * 对 RESTful 的支持  
  在绑定路由的时候使用第三个参数设置路由的请求方法:
    ```php
    Route::route('id/{id}', 'Index@index', Route::ROUTE_GET|Route::ROUTE_POST);
    ```
  多个请求方法之间用按位或(|)连接.  
  另外, 在控制器里面可以通过 `request_method()` 来获取当前请求的方法, 返回值是 `Route` 的类常量.
