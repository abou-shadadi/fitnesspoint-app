<?php

namespace App\Models\Transaction;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DebitTransaction extends Model
{

    protected $guarded = [];
    use HasFactory;

    public function user()
    {

        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function budget_income()
    {

        return $this->belongsTo(\App\Models\Budget\BudgetIncome::class, 'budget_income_id');
    }


    public function income_item()
    {

        return $this->belongsTo(\App\Models\Income\IncomeItem::class, 'income_item_id');
    }
}
