<?php

namespace App\Http\Controllers\Backend\Additional;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ZipArchive;

class AddonController extends Controller
{
    public function index()
    {
        $addons = Addon::all();
        return view('backend.additional.addons.index', ['addons' => $addons]);
    }

    public function store(Request $request)
    {
        if (parse_url(url('/'))['host'] == 'localhost') {
            toastr()->error(__('Addons cannot be installed on local server'));
            return back();
        }
        if (!class_exists('ZipArchive')) {
            toastr()->error(__('ZipArchive extension is not enabled'));
            return back();
        }
        if (!$request->hasFile('addon_files')) {
            toastr()->error(__('Addon files required'));
            return back();
        }
        if (empty($request->purchase_code)) {
            toastr()->error(__('Purchase code is required'));
            return back();
        }
        if (!preg_match("/^([a-f0-9]{8})-(([a-f0-9]{4})-){3}([a-f0-9]{12})$/i", $request->purchase_code)) {
            toastr()->error(__('Invalid purchase code'));
            return back();
        }
        $addonZip = $request->addon_files;
        $addonZipFileExt = $addonZip->getClientOriginalExtension();
        if ($addonZipFileExt != "zip") {
            toastr()->error(__('File type not allowed'));
            return back();
        }
        try {
            $uploadPath = vFileUpload($addonZip, 'addons/temp/');
            $zip = new ZipArchive;
            $res = $zip->open($uploadPath);
            if ($res != true) {
                removeFile($uploadPath);
                toastr()->error(__('Could not open the zip file'));
                return back();
            }
            $dir = trim($zip->getNameIndex(0), '/');
            $addonsPath = base_path('addons/temp/');
            $thisAddonPath = base_path('addons/temp/' . $dir);
            if (File::exists($thisAddonPath)) {
                removeDirectory($thisAddonPath);
            }
            $res = $zip->extractTo($addonsPath);
            if ($res == true) {
                removeFile($uploadPath);
            }
            $zip->close();
            if (!File::exists($thisAddonPath . '/config.json')) {
                removeFile($thisAddonPath);
                toastr()->error(__('Config.json is missing'));
                return back();
            }
            $str = file_get_contents($thisAddonPath . '/config.json');
            $json = json_decode($str, true);
            $purchaseValidate = $this->addonPurchaseCodeValidate($request->purchase_code, $json['symbol'], $json['version']);
            if ($purchaseValidate->status != "success") {
                removeDirectory($thisAddonPath);
                toastr()->error($purchaseValidate->message);
                return back();
            }
            if (strtolower(config('vironeer.item.alias')) != $json['script_symbol']) {
                removeDirectory($thisAddonPath);
                toastr()->error(__('Invalid action request'));
                return back();
            }
            if (config('vironeer.item.version') < $json['minimal_script_version']) {
                removeDirectory($thisAddonPath);
                toastr()->error(__('Addon require version ' . $json['minimal_script_version'] . ' or above'));
                return back();
            }
            $addonExist = Addon::where('symbol', $json['symbol'])->first();
            if ($addonExist) {
                toastr()->error(__('Addon is already exists'));
                return back();
            }
            if (!empty($json['remove_directories'])) {
                foreach ($json['remove_directories'] as $remove_directory) {
                    removeDirectory($remove_directory);
                }
            }
            if (!empty($json['remove_files'])) {
                foreach ($json['remove_files'] as $remove_file) {
                    removeFile($remove_file);
                }
            }
            if (!empty($json['directories'])) {
                foreach ($json['directories'][0]['assets'] as $assets_directory) {
                    makeDirectory($assets_directory);
                }
                foreach ($json['directories'][0]['files'] as $files_directory) {
                    makeDirectory(base_path($files_directory));
                }
            }
            if (!empty($json['assets'])) {
                foreach ($json['assets'] as $asset) {
                    File::copy(base_path($asset['root_directory']), $asset['update_directory']);
                }
            }
            if (!empty($json['files'])) {
                foreach ($json['files'] as $file) {
                    File::copy(base_path($file['root_directory']), base_path($file['update_directory']));
                }
            }
            if (file_exists(base_path($json['sql_file']))) {
                DB::unprepared(file_get_contents(base_path($json['sql_file'])));
            }
            $createAddon = Addon::create([
                "api_key" => $purchaseValidate->data->api_key,
                "logo" => $json['logo'],
                "name" => $json['name'],
                "symbol" => strtolower($json['symbol']),
                "version" => $json['version'],
                'action_text' => $json['action_text'],
                'action_link' => $json['action_link'],
                "status" => 1,
            ]);
            if ($createAddon) {
                removeDirectory($thisAddonPath);
                toastr()->success(__('Addon has been installed successfully'));
                return back();
            }
        } catch (\Exception $e) {
            removeFile($uploadPath);
            removeDirectory($thisAddonPath);
            toastr()->error($e->getMessage());
            return back();
        }

    }

    protected function addonPurchaseCodeValidate($purchaseCode, $symbol, $version)
    {
        $website = url('/');
        $client = new \GuzzleHttp\Client();
        $request = $client->get(config('vironeer.api.license') . "?purchaseCode={$purchaseCode}&website={$website}&symbol={$symbol}&version={$version}");
        $response = json_decode($request->getBody());
        return $response;
    }

    public function edit(Addon $addon)
    {
        return view('backend.additional.addons.edit', ['addon' => $addon]);
    }

    public function update(Request $request, Addon $addon)
    {
        if (parse_url(url('/'))['host'] == 'localhost') {
            toastr()->error(__('Addons cannot be installed on local server'));
            return back();
        }
        if (!class_exists('ZipArchive')) {
            toastr()->error(__('ZipArchive extension is not enabled'));
            return back();
        }
        if (!$request->hasFile('addon_files')) {
            toastr()->error(__('Addon files required'));
            return back();
        }
        $addonZip = $request->addon_files;
        $addonZipFileExt = $addonZip->getClientOriginalExtension();
        if ($addonZipFileExt != "zip") {
            toastr()->error(__('File type not allowed'));
            return back();
        }
        try {
            $uploadPath = vFileUpload($addonZip, 'addons/temp/');
            $zip = new ZipArchive;
            $res = $zip->open($uploadPath);
            if ($res != true) {
                removeFile($uploadPath);
                toastr()->error(__('Could not open the zip file'));
                return back();
            }
            $dir = trim($zip->getNameIndex(0), '/');
            $addonsPath = base_path('addons/temp/');
            $thisAddonPath = base_path('addons/temp/' . $dir);
            if (File::exists($thisAddonPath)) {
                removeDirectory($thisAddonPath);
            }
            $res = $zip->extractTo($addonsPath);
            if ($res == true) {
                removeFile($uploadPath);
            }
            $zip->close();
            if (!File::exists($thisAddonPath . '/config.json')) {
                removeFile($thisAddonPath);
                toastr()->error(__('Config.json is missing'));
                return back();
            }
            $str = file_get_contents($thisAddonPath . '/config.json');
            $json = json_decode($str, true);
            $purchaseValidate = $this->addonApiKeyValidate($addon->api_key, $addon->symbol);
            if ($purchaseValidate->status != "success") {
                removeDirectory($thisAddonPath);
                toastr()->error($purchaseValidate->message);
                return back();
            }
            if ($addon->symbol != $json['symbol']) {
                removeDirectory($thisAddonPath);
                toastr()->error(__('Invalid action request'));
                return back();
            }
            if (strtolower(config('vironeer.item.alias')) != $json['script_symbol']) {
                removeDirectory($thisAddonPath);
                toastr()->error(__('Invalid action request'));
                return back();
            }
            if (config('vironeer.item.version') < $json['minimal_script_version']) {
                removeDirectory($thisAddonPath);
                toastr()->error(__('Addon require version ' . $json['minimal_script_version'] . ' or above'));
                return back();
            }
            if (!empty($json['remove_directories'])) {
                foreach ($json['remove_directories'] as $remove_directory) {
                    removeDirectory($remove_directory);
                }
            }
            if (!empty($json['remove_files'])) {
                foreach ($json['remove_files'] as $remove_file) {
                    removeFile($remove_file);
                }
            }
            if (!empty($json['directories'])) {
                foreach ($json['directories'][0]['assets'] as $assets_directory) {
                    makeDirectory($assets_directory);
                }
                foreach ($json['directories'][0]['files'] as $files_directory) {
                    makeDirectory(base_path($files_directory));
                }
            }
            if (!empty($json['assets'])) {
                foreach ($json['assets'] as $asset) {
                    File::copy(base_path($asset['root_directory']), $asset['update_directory']);
                }
            }

            if (!empty($json['files'])) {
                foreach ($json['files'] as $file) {
                    File::copy(base_path($file['root_directory']), base_path($file['update_directory']));
                }
            }
            if (file_exists(base_path($json['sql_file']))) {
                DB::unprepared(file_get_contents(base_path($json['sql_file'])));
            }
            $status = ($request->has('status')) ? 1 : 0;
            $updateAddon = $addon->update([
                "version" => $json['version'],
                'action_text' => $json['action_text'],
                'action_link' => $json['action_link'],
                "status" => $status,
            ]);
            if ($updateAddon) {
                removeDirectory($thisAddonPath);
                toastr()->success(__('Addon has been updated successfully'));
                return back();
            }
        } catch (\Exception $e) {
            removeFile($uploadPath);
            removeDirectory($thisAddonPath);
            toastr()->error($e->getMessage());
            return back();
        }
    }

    protected function addonApiKeyValidate($api_key, $symbol)
    {
        $website = url('/');
        $client = new \GuzzleHttp\Client();
        $request = $client->get(config('vironeer.api.license') . "/details?apikey={$api_key}&website={$website}&symbol={$symbol}");
        $response = json_decode($request->getBody());
        return $response;
    }
}
