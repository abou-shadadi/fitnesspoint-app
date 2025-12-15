<?php

namespace App\Models\Transaction;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditTransaction extends Model
{
    protected $guarded = [];
    use HasFactory;

    public function user()
    {

        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
    public function budget_expense()
    {

        return $this->belongsTo(\App\Models\Budget\BudgetExpense::class, 'budget_expense_id');
    }


    public function expense_item()
    {

        return $this->belongsTo(\App\Models\Expense\ExpenseItem::class, 'expense_item_id');
    }

}
