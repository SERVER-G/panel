<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Validation\Rules\In;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class VersionController extends Controller
{
    /**
     * @var /Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * VersionController constructor.
     * @param AlertsMessageBag $alert
     */
    public function __construct(AlertsMessageBag $alert)
    {
        $this->alert = $alert;
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function groups()
    {
        $groups = DB::table('version_groups')->orderBy('sort', 'ASC')->get();
        $eggs = DB::table('eggs')->get();

        return view('admin.version.groups', [
            'groups' => $groups,
            'eggs' => $eggs
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function createGroup(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|min:3|max:191',
            'eggs' => 'required',
            'type' => 'required|integer',
        ]);

        $name = trim(strip_tags($request->input('name')));
        $eggs = $request->input('eggs');
        $type = (int) $request->input('type');

        if (!in_array($type, [1, 2, 3, 4])) {
            $this->alert->danger('Invalid group type.')->flash();

            return redirect()->route('admin.version')->withInput(Input::all());
        }

        if (in_array($type, [2, 3])) {
            $this->validate($request, [
                'folder' => 'required|min:2|max:191',
            ]);
        }

        $folder = $request->input('folder', null);

        foreach ($eggs as $egg) {
            $egg = DB::table('eggs')->where('id', '=', $egg)->get();
            if (count($egg) < 1) {
                $this->alert->danger('Egg not found.')->flash();

                return redirect()->route('admin.version')->withInput(Input::all());
            }
        }

        $sort = DB::table('version_groups')->orderBy('sort', 'DESC')->get();
        count($sort) < 1 ? $sort = 1 : $sort = $sort[0]->sort + 1;

        DB::table('version_groups')->insert([
            'name' => $name,
            'eggs' => serialize($eggs),
            'type' => $type,
            'folder' => $folder,
            'sort' => $sort
        ]);

        $this->alert->success('You have successfully added this group!')->flash();

        return redirect()->route('admin.version');
    }

    /**
     * @param Request $request
     * @param $group
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function editGroup(Request $request, $group)
    {
        $group = DB::table('version_groups')->where('id', '=', (int) $group)->get();
        if (count($group) < 1) {
            $this->alert->danger('Group not found.')->flash();

            return redirect()->route('admin.version')->withInput(Input::all());
        }

        $this->validate($request, [
            'name' => 'required|min:3|max:191',
            'eggs' => 'required',
        ]);

        $name = trim(strip_tags($request->input('name')));
        $eggs = $request->input('eggs');

        if (in_array($group[0]->type, [2, 3])) {
            $this->validate($request, [
                'folder' => 'required|min:2|max:191',
            ]);
        }

        $folder = $request->input('folder', null);

        foreach ($eggs as $egg) {
            $egg = DB::table('eggs')->where('id', '=', $egg)->get();
            if (count($egg) < 1) {
                $this->alert->danger('Egg not found.')->flash();

                return redirect()->route('admin.version')->withInput(Input::all());
            }
        }

        DB::table('version_groups')->where('id', '=', $group[0]->id)->update([
            'name' => $name,
            'eggs' => serialize($eggs),
            'folder' => $folder,
        ]);

        $this->alert->success('You have successfully edited this group!')->flash();

        return redirect()->route('admin.version');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteGroup(Request $request)
    {
        $id = (int) $request->input('id', 0);

        $group = DB::table('version_groups')->where('id', '=', $id)->get();
        if (count($group) < 1) {
            return response()->json(['error' => 'Group not found.'], 404);
        }

        DB::table('version_groups')->where('id', '=', $id)->delete();
        DB::table('versions')->where('group_id', '=', $id)->delete();

        return response()->json(['success' => true]);
    }

    /**
     * @param $group
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function versions($group)
    {
        $group = DB::table('version_groups')->where('id', '=', (int) $group)->get();
        if (count($group) < 1) {
            throw new NotFoundHttpException();
        }

        if (in_array($group[0]->type, [2, 4])) {
            throw new NotFoundHttpException();
        }

        $versions = DB::table('versions')->where('group_id', '=', $group[0]->id)->orderBy('sort', 'ASC')->get();

        return view('admin.version.versions', [
            'group' => $group[0],
            'versions' => $versions
        ]);
    }

    /**
     * @param Request $request
     * @param $group
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function createVersion(Request $request, $group)
    {
        $group = DB::table('version_groups')->where('id', '=', (int) $group)->get();
        if (count($group) < 1) {
            throw new NotFoundHttpException();
        }

        if ($group[0]->type == 2 || $group[0]->type == 4) {
            throw new NotFoundHttpException();
        }

        $this->validate($request, [
            'name' => 'required|min:3|max:191',
            'filename' => 'required|min:5|max:191',
        ]);

        if ($group[0]->type == 1) {
            $this->validate($request, [
                'download_url' => 'required|min:3|max:191',
                'action' => 'required|integer',
            ]);

            $url = $request->input('download_url');
        }

        if ($group[0]->type == 3) {
            $this->validate($request, [
                'key' => 'required|min:5|max:191',
            ]);

            $url = $request->input('key');
        }

        $filename = trim(strip_tags($request->input('filename')));
        $action = $request->input('action', 0);

        if (strpos($filename, '.jar') === false) {
            $this->alert->danger('Invalid filename format. It needs to contain .jar.')->flash();

            return redirect()->route('admin.version.versions', $group[0]->id)->withInput(Input::all());
        }

        $sort = DB::table('versions')->where('group_id', '=', $group[0]->id)->orderBy('sort', 'DESC')->get();
        count($sort) < 1 ? $sort = 1 : $sort = $sort[0]->sort + 1;

        DB::table('versions')->insert([
            'group_id' => $group[0]->id,
            'name' => trim(strip_tags($request->input('name'))),
            'download_url' => $url,
            'filename' => $filename,
            'action' => $action,
            'sort' => $sort,
        ]);

        $this->alert->success('You have successfully added this version!')->flash();

        return redirect()->route('admin.version.versions', $group[0]->id);
    }

    /**
     * @param Request $request
     * @param $group
     * @param $version
     * @return \Illuminate\Http\RedirectResponse
     * @throws 2276 \Illuminate\Validation\ValidationException
     */
    public function editVersion(Request $request, $group, $version)
    {
        $group = DB::table('version_groups')->where('id', '=', (int) $group)->get();
        if (count($group) < 1) {
            $this->alert->danger('Group not found.')->flash();

            return redirect()->route('admin.version.versions', $group[0]->id)->withInput(Input::all());
        }

        $version = DB::table('versions')->where('id', '=', (int) $version)->where('group_id', '=', (int) $group[0]->id)->get();
        if (count($version) < 1) {
            $this->alert->danger('Version not found.')->flash();

            return redirect()->route('admin.version.versions', $group[0]->id)->withInput(Input::all());
        }

        $this->validate($request, [
            'name' => 'required|min:3|max:191',
            'filename' => 'required|min:5|max:191',
        ]);

        if ($group[0]->type == 1) {
            $this->validate($request, [
                'download_url' => 'required|min:3|max:191',
            ]);

            $url = $request->input('download_url');
        }

        if ($group[0]->type == 3) {
            $this->validate($request, [
                'key' => 'required|min:5|max:191',
            ]);

            $url = $request->input('key');
        }

        $filename = trim(strip_tags($request->input('filename')));

        if (strpos($filename, '.jar') === false) {
            $this->alert->danger('Invalid filename format. It needs to contain .jar.')->flash();

            return redirect()->route('admin.version.versions', $group[0]->id)->withInput(Input::all());
        }

        DB::table('versions')->where('id', '=', $version[0]->id)->update([
            'name' => trim(strip_tags($request->input('name'))),
            'download_url' => $url,
            'filename' => $filename,
        ]);

        $this->alert->success('You have successfully edited this version!')->flash();

        return redirect()->route('admin.version.versions', $group[0]->id);
    }

    /**
     * @param Request $request
     * @param $group
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteVersion(Request $request, $group)
    {
        $id = (int) $request->input('id');

        $version = DB::table('versions')->where('id', '=', $id)->where('group_id', '=', (int) $group)->get();
        if (count($version) < 1) {
            return response()->json(['error' => 'Version not found.'], 404);
        }

        DB::table('versions')->where('id', '=', $id)->delete();

        return response()->json(['success' => true]);
    }
}
