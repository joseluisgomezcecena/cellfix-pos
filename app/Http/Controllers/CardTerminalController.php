<?php

namespace App\Http\Controllers;

use App\CardTerminal;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CardTerminalController extends Controller
{
    public function index()
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $terminals = CardTerminal::where('business_id', $business_id)
                ->select(['id', 'name', 'bank', 'account_number', 'is_active']);

            return DataTables::of($terminals)
                ->addColumn('action', function ($row) {
                    $html = '<button type="button" data-href="' . route('card-terminals.edit', [$row->id]) .
                        '" class="btn btn-xs btn-primary btn-modal" data-container=".terminal_modal">' .
                        '<i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</button>';
                    $html .= ' <button type="button" data-href="' . route('card-terminals.destroy', [$row->id]) .
                        '" class="btn btn-xs btn-danger delete_card_terminal_button">' .
                        '<i class="glyphicon glyphicon-trash"></i> ' . __('messages.delete') . '</button>';

                    return $html;
                })
                ->editColumn('is_active', function ($row) {
                    return $row->is_active
                        ? '<span class="label label-success">' . __('business.is_active') . '</span>'
                        : '<span class="label label-default">' . __('lang_v1.inactive') . '</span>';
                })
                ->rawColumns(['action', 'is_active'])
                ->make(true);
        }

        return view('card_terminal.index');
    }

    public function create()
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        return view('card_terminal.create');
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['name', 'bank', 'account_number']);
            $input['business_id'] = $request->session()->get('user.business_id');
            $input['is_active'] = $request->has('is_active') ? 1 : 0;

            CardTerminal::create($input);

            $output = ['success' => true, 'msg' => __('lang_v1.added_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    public function edit($id)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $terminal = CardTerminal::where('business_id', $business_id)->findOrFail($id);

            return view('card_terminal.edit', compact('terminal'));
        }
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $terminal = CardTerminal::where('business_id', $business_id)->findOrFail($id);

            $input = $request->only(['name', 'bank', 'account_number']);
            $input['is_active'] = $request->has('is_active') ? 1 : 0;
            $terminal->update($input);

            $output = ['success' => true, 'msg' => __('lang_v1.updated_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    public function destroy($id)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $terminal = CardTerminal::where('business_id', $business_id)->findOrFail($id);
            $terminal->delete();

            $output = ['success' => true, 'msg' => __('lang_v1.deleted_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }
}
