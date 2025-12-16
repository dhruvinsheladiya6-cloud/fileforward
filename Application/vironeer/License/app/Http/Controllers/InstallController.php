<?php

namespace Vironeer\License\App\Http\Controllers;

use App\Models\Admin;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InstallController extends Controller
{
    public function redirect()
    {
        return redirect()->route('install.requirements');
    }

    public function redirectToDatabase()
    {
        return redirect()->route('install.information.database');
    }

    protected function extensionsArray()
    {
        $extensions = phpExtensions();
        $extensionsArray = [];
        foreach ($extensions as $extension) {$extensionsArray[] = extensionAvailability($extension);}
        return $extensionsArray;
    }

    public function requirements()
    {
        if (config('vironeer.install.requirements')) {
            return redirect()->route('install.permissions');
        }

        $error = 0;
        $extensions = phpExtensions();
        if (in_array(false, $this->extensionsArray())) {
            $error = 1;
        }

        return view('vironeer::requirements', ['extensions' => $extensions, 'error' => $error]);
    }

    public function requirementsAction(Request $request)
    {
        if (in_array(false, $this->extensionsArray())) {
            return redirect()->route('install.requirements');
        }

        \Artisan::call('key:generate');
        setEnv('APP_ENV', 'production');
        setEnv('VIRONEER_REQUIREMENTS', 1);

        return redirect()->route('install.permissions');
    }

    protected function permissionsArray()
    {
        $permissions = filePermissions();
        $permissionsArray = [];
        foreach ($permissions as $permission) {
            $permissionsArray[] = filePermissionValidation($permission);
        }
        return $permissionsArray;
    }

    public function permissions()
    {
        if (config('vironeer.install.file_permissions')) {return redirect()->route('install.licence');}
        if (!config('vironeer.install.requirements')) {return redirect()->route('install.requirements');}

        $error = 0;
        $permissions = filePermissions();
        if (in_array(false, $this->permissionsArray())) {
            $error = 1;
        }
        return view('vironeer::permissions', ['permissions' => $permissions, 'error' => $error]);
    }

    public function permissionsAction(Request $request)
    {
        if (in_array(false, $this->permissionsArray())) {
            return redirect()->route('install.permissions');
        }

        setEnv('VIRONEER_FILEPERMISSIONS', 1);
        return redirect()->route('install.licence');
    }

    public function licence()
    {
        if (config('vironeer.install.license')) {
            return redirect()->route('install.information.database');
        }

        if (!config('vironeer.install.file_permissions')) {
            return redirect()->route('install.requirements');
        }

        return view('vironeer::licence');
    }

    public function licenceAction(Request $request)
    {
        if (!in_array(parse_url(url('/'))['host'], ['localhost', '127.0.0.1'])) {
            if (empty($request->purchase_code)) {
                return redirect()->back()->withErrors(['Purchase code is required']);
            }
            if (!preg_match("/^([a-f0-9]{8})-(([a-f0-9]{4})-){3}([a-f0-9]{12})$/i", $request->purchase_code)) {
                return redirect()->back()->withErrors(['Invalid purchase code']);
            }
            try {
                $purchaseCode = $request->purchase_code;
                $website = url('/');
                $symbol = config('vironeer.item.alias');
                $version = config('vironeer.item.version');
                $client = new \GuzzleHttp\Client();
                $request = $client->get(config('vironeer.api.license') . "?purchaseCode={$purchaseCode}&website={$website}&symbol={$symbol}&version={$version}");
                $response = json_decode($request->getBody());
                if ($response->status == "error") {
                    return redirect()->back()->withErrors([$response->message]);
                }
                setEnv('LICENCE_TYPE', $response->data->licence_type);
                setEnv('VIRONEER_LICENCE', 1);
                return redirect()->route('install.information.database');
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                return redirect()->back()->withErrors(['Request error, please try again']);
            }
        } else {
            setEnv('VIRONEER_LICENCE', 1);
            return redirect()->route('install.information.database');
        }
    }

    public function database()
    {
        if (config('vironeer.install.database_info')) {
            return redirect()->route('install.information.databaseImport');
        }

        if (!config('vironeer.install.license')) {
            return redirect()->route('install.licence');
        }

        return view('vironeer::information.database');
    }

    public function databaseAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'db_host' => ['required', 'string'],
            'db_name' => ['required', 'string'],
            'db_user' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if (!function_exists('curl_version')) {
            return redirect()->back()->withErrors(['CURL does not exist in server'])->withInput();
        }

        if (!is_writable(base_path('.env'))) {
            return redirect()->back()->withErrors(['.ENV file is not writable'])->withInput();
        }

        if (!@mysqli_connect($request->db_host, $request->db_user, $request->db_pass, $request->db_name)) {
            return redirect()->back()->withErrors(['Incorrect database information'])->withInput();
        }

        setEnv('DB_HOST', $request->db_host);
        setEnv('DB_DATABASE', $request->db_name);
        setEnv('DB_USERNAME', $request->db_user);
        setEnv('DB_PASSWORD', $request->db_pass, true);
        setEnv('VIRONEER_INFODATABASE', 1);

        return redirect()->route('install.information.databaseImport');
    }

    public function databaseImport()
    {
        if (config('vironeer.install.database_import')) {
            return redirect()->route('install.information.building');
        }

        if (!config('vironeer.install.database_info')) {
            return redirect()->route('install.information.database');
        }

        return view('vironeer::information.databaseImport');
    }

    public function databaseImportAction(Request $request)
    {
        if (!file_exists(base_path('database/sql/data.sql'))) {
            return redirect()->back()->withErrors(['SQL file is missing'])->withInput();
        }
        try {
            DB::connection()->getPdo();
            if (DB::connection()->getDatabaseName()) {
                $sql = base_path('database/sql/data.sql');
                $import = DB::unprepared(file_get_contents($sql));
                if ($import) {
                    setEnv('VIRONEER_INFODBIMPORT', 1);
                    return redirect()->route('install.information.building');
                } else {
                    return redirect()->back()->withErrors(['Error']);
                }
            } else {
                return redirect()->back()->withErrors(['Could not find the database. Please check your configuration.']);
            }
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([$e->getMessage()]);
        }
    }

    public function downloadSqlFile()
    {
        $sql = base_path('database/sql/data.sql');
        if (!file_exists($sql)) {
            return redirect()->back()->withErrors(['SQL file missing']);
        }

        return response()->download($sql);
    }

    public function databaseImportSkip()
    {
        setEnv('VIRONEER_INFODBIMPORT', 1);
        return redirect()->route('install.information.building');
    }

    public function building()
    {

        if (!config('vironeer.install.database_import')) {
            return redirect()->route('install.information.databaseImport');
        }

        if (config('vironeer.install.complete')) {
            return redirect('/');
        }

        return view('vironeer::information.building');
    }

    public function backToDatabaseImport()
    {
        setEnv('VIRONEER_INFODBIMPORT', '');
        return redirect()->route('install.information.database');
    }

    public function buildingAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'website_name' => ['required', 'string', 'max:200'],
            'website_url' => ['required', 'url'],
            'email' => ['required', 'string', 'email', 'unique:admins'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $avatar = 'images/avatars/default.png';
        $createAdmin = Admin::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'avatar' => $avatar,
        ]);

        if ($createAdmin) {

            $settings = Settings::whereIn('key', ['website_name', 'website_url'])->get();
            foreach ($settings as $setting) {
                $key = $setting->key;
                $setting->value = $request->$key;
                $setting->save();
            }

            setEnv('APP_URL', $request->website_url);
            setEnv('VIRONEER_INFOBUILDING', 1);
            return redirect()->route('admin.index');
        }
    }
}
