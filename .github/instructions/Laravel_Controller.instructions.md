---
applyTo: '{app/Http/Controllers/**,Modules/**/Http/Controllers/**}'
---
# Laravel Controller Standards

## General Principles

- Controllers should follow the Single Responsibility Principle
- Keep controllers thin - business logic belongs in repositories or services
- Use dependency injection via constructor for required services/repositories
- All methods must use PHP type declarations (parameters and return types)
- Follow PSR-12 coding standards

## Structure and Naming

- Use singular noun for controller names (e.g., `UserController` not `UsersController`)
- Follow RESTful naming for resource controllers:
  - `index()` - Display a listing of resources
  - `create()` - Show creation form
  - `store()` - Store a newly created resource
  - `show()` - Display a specific resource
  - `edit()` - Show edit form for a resource
  - `update()` - Update a specific resource
  - `destroy()` - Delete a specific resource
- Custom methods should use descriptive verbs (e.g., `approveSubscription()`)

## Input Handling

- Move validations to dedicated Form Request classes
- Type-hint Form Request classes in controller methods
- Never trust user input - always validate
- Example:
  ```php
  public function store(StoreUserRequest $request): RedirectResponse
  {
      // $request is already validated
      $this->userRepository->create($request->validated());
      return redirect()->route('users.index')->with('success', 'User created successfully');
  }
  ```

## Dependency Injection

- Use constructor injection for repositories and services
- Avoid using facades in controllers when possible
- Use method injection for request-specific dependencies
- Example:
  ```php
  class UserController extends Controller
  {
      protected UserRepository $userRepository;
      
      public function __construct(UserRepository $userRepository)
      {
          $this->userRepository = $userRepository;
      }
  }
  ```

## Response Handling

- Return consistent response formats
- For APIs, return appropriate HTTP status codes
- Use resource/collection classes for API responses
- For web routes, return view responses with compact data
- Example API response:
  ```php
  return response()->json([
      'data' => UserResource::collection($users),
      'meta' => [
          'total' => $users->total(),
          'per_page' => $users->perPage()
      ]
  ], 200);
  ```

## Error Handling

- Use Laravel's exception handler for handling errors
- Return appropriate error responses with status codes
- Add contextual error messages
- Avoid try-catch blocks in controllers when possible

## Authorization

- Use Gates and Policies for authorization logic
- Implement authorization checks in controllers with `authorize()` method
- Avoid complex permission logic in controllers
- Example:
  ```php
  public function update(UpdateUserRequest $request, User $user): RedirectResponse
  {
      $this->authorize('update', $user);
      $this->userRepository->update($user, $request->validated());
      return redirect()->route('users.index')->with('success', 'User updated successfully');
  }
  ```

## Documentation

- Document all public methods with PHPDoc blocks
- Include @param and @return tags
- Document thrown exceptions with @throws
- Example:
  ```php
  /**
   * Display a listing of users.
   *
   * @param Request $request
   * @return \Illuminate\View\View
   */
  public function index(Request $request): View
  {
      $users = $this->userRepository->getAllWithPagination($request->get('per_page', 15));
      return view('users.index', compact('users'));
  }
  ```

## Testing

- Each controller must have corresponding feature tests
- Test all routes and response types
- Test authorization and validation rules
- Ensure >90% test coverage
- Example test:
  ```php
  public function test_user_can_be_created(): void
  {
      $this->actingAs($this->admin)
          ->post(route('users.store'), [
              'name' => 'Test User',
              'email' => 'test@example.com',
              'password' => 'password',
              'password_confirmation' => 'password',
          ])
          ->assertRedirect(route('users.index'))
          ->assertSessionHas('success');
          
      $this->assertDatabaseHas('users', [
          'email' => 'test@example.com',
      ]);
  }
  ```

## No Business Logic in Controllers

- Controllers should delegate business logic to services or repositories
- Bad:
  ```php
  public function store(Request $request): RedirectResponse
  {
      $validated = $request->validate([/* ... */]);
      
      if ($request->hasFile('image')) {
          $path = $request->file('image')->store('users');
          $validated['image_path'] = $path;
      }
      
      $user = User::create($validated);
      event(new UserCreated($user));
      
      return redirect()->route('users.index');
  }
  ```
- Good:
  ```php
  public function store(StoreUserRequest $request): RedirectResponse
  {
      $user = $this->userService->createUser($request->validated());
      return redirect()->route('users.index')->with('success', 'User created successfully');
  }
  ```

## Avoid Queries in Controllers

- Use repositories or services for database queries
- Don't execute queries directly in controllers
- Don't use Eloquent methods like `::all()`, `::find()`, etc. directly in controllers

## No View Logic in Controllers

- Use view composers or separate services for complex view data preparation
- Keep controllers focused on HTTP concerns, not view presentation logic

## Module Controllers

- Controllers in modules should follow the same standards as application controllers
- Place module controllers in `Modules/{ModuleName}/Http/Controllers` directory
- Use the module's namespace for controller classes (e.g., `namespace Modules\User\Http\Controllers;`)
- For API controllers in modules, place them in `Modules/{ModuleName}/Http/Controllers/API` directory
- Module controllers should only depend on services and repositories from their own module or core modules
- Example module controller structure:
  ```php
  namespace Modules\User\Http\Controllers;
  
  use Illuminate\Contracts\View\View;
  use Illuminate\Http\RedirectResponse;
  use Modules\Core\Http\Controllers\CoreController;
  use Modules\User\Http\Requests\CreateUserRequest;
  use Modules\User\Repositories\UserRepository;
  
  class UserController extends CoreController
  {
      protected UserRepository $userRepository;
      
      public function __construct(UserRepository $userRepository)
      {
          $this->userRepository = $userRepository;
      }
      
      public function index(): View
      {
          $users = $this->userRepository->getAllWithPagination();
          return view('user::index', compact('users'));
      }
      
      public function store(CreateUserRequest $request): RedirectResponse
      {
          $this->userRepository->create($request->validated());
          return redirect()->route('user.index')->with('success', 'User created successfully');
      }
  }
  ```