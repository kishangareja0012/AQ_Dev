<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\User;

class LoginController extends Controller
{
	use AuthenticatesUsers;

	protected $guard = 'admin';

	protected $redirectTo = '/admin/dashboard';

	public function __construct()
    {
      $this->middleware('guest:admin', ['except' => ['logout']]);
    }

	public function showLoginForm()
	{
		return view('admin.login');
	}

	protected function guard()
	{
		return Auth::guard($this->guard);
	}
	
	public function index(Request $request)
    {
		return redirect('admin/login');
	}

	public function login(Request $request)
    {
      // Validate the form data
      $this->validate($request, [
        'email'   => 'required|email',
        'password' => 'required|min:6'
      ]);
       // Attempt to log the user in
      if (Auth::attempt(['email' => $request->email, 'password' => $request->password], $request->remember)) {
			//$user = User:: where('email', $request->email)->first;
            //$user = User:: find( $request->email);
			//$user->lastActive = timestamps();
			//$user->save();
        // if successful, then redirect to their intended location
        return redirect()->intended(url('admin/dashboard'));
      }
      // if unsuccessful, then redirect back to the login with the form data
      return redirect()->intended(url('admin/dashboard'))->withInput($request->only('email', 'remember'));
    }

	public function logout()
    {
        Auth::guard('admin')->logout();
        return redirect('/admin');
    }
}
