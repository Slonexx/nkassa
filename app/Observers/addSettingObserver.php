<?php

namespace App\Observers;

use App\Models\documentModel;
use Illuminate\Support\Facades\DB;

class addSettingObserver
{
    public function created(documentModel $model)
    {

        $accountIds = documentModel::all('accountId');

        foreach($accountIds as $accountId){

            $query = documentModel::query();
            $logs = $query->where('accountId',$accountId->accountId)->get();
            if(count($logs) > 1){
                DB::table('document_models')
                    ->where('accountId','=',$accountId->accountId)
                    ->orderBy('created_at', 'ASC')
                    ->limit(1)
                    ->delete();
            }

        }

    }


    public function updated(documentModel $model)
    {
        //
    }

    public function deleted(documentModel $model)
    {
        //
    }

    public function restored(documentModel $model)
    {
        //
    }

    public function forceDeleted(documentModel $model)
    {
        //
    }

}
