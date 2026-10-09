<?php

namespace App\Http\Controllers;

use App\Services\Calculator;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CalculatorController extends Controller
{
   
    public function index(Request $request)
    {
        return view('calculator', [
            'expression' => (string) $request->query('expression', ''),
            'result'     => $request->query('result'),
            'error'      => $request->query('error'),
        ]);
    }

    
    public function calculate(Request $request, Calculator $calculator)
    {
        $expression = (string) $request->input('expression', '');

        try {
            $result = $calculator->evaluate($expression);

            return redirect()->route('calc', [
                'expression' => $expression,
                'result'     => $result,
            ]);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('calc', [
                'expression' => $expression,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
