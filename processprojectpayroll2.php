<?php
// namespacing
use Core\FH;
use Core\H;
use Core\Input;
use App\Models\PayrollItems;
use App\Models\Payrolls;
use App\Models\PayrollDetails;
use App\Models\Employees;
use App\Models\ProjectBudgetlineRefs;
use App\Models\Projects;
use App\Models\TaxAuths;

$pjList = "";
$payroll_List = "";
$empGrossIncomeArray = [];
$empList = "";

/*************************     
Fetch from payroll_details table()
 *************************/
$payrollDetails = PayrollDetails::findByprojectId($this->project_id);
//  H::dnd($payrollDetails);
if ($payrollDetails) {
  for ($a = 0; $a < count($payrollDetails); $a++) {
    $payrollDetailsArray[$payrollDetails[$a]->employee_id][$payrollDetails[$a]->item_id]['id'] = $payrollDetails[$a]->id;
    $payrollDetailsArray[$payrollDetails[$a]->employee_id][$payrollDetails[$a]->item_id]['project_id'] = ($payrollDetails[$a]->project_id) ? $payrollDetails[$a]->project_id : "";
    $payrollDetailsArray[$payrollDetails[$a]->employee_id][$payrollDetails[$a]->item_id]['project_budgetline_ref_id'] = ($payrollDetails[$a]->project_budgetline_ref_id) ? $payrollDetails[$a]->project_budgetline_ref_id : "";
    $payrollDetailsArray[$payrollDetails[$a]->employee_id][$payrollDetails[$a]->item_id]['tax_auth_id'] = ($payrollDetails[$a]->tax_auth_id) ? $payrollDetails[$a]->tax_auth_id : "";
    $payrollDetailsArray[$payrollDetails[$a]->employee_id][$payrollDetails[$a]->item_id]['amount'] = ($payrollDetails[$a]->amount) ? $payrollDetails[$a]->amount : "";
  }
}

// H::dnd($payrollDetailsArray);

/*************************     
Fetch from payroll_Items table
 *************************/
$payroll_Items = PayrollItems::find(
  [
    'conditions' => "is_active = 1",
    'order' => "code"
  ]
);
for ($a = 0; $a < count($payroll_Items); $a++) {
  if ($payroll_Items[$a]->id == '') {
    continue;
  }
  $prolItemValues[$a]    = $payroll_Items[$a]->id; //used for project id values
  $prolItemDisplays[$a]    = $payroll_Items[$a]->code . '::' . $payroll_Items[$a]->name; //used for Display of payroll_Items
  $prolItemCodes[$a]    = $payroll_Items[$a]->code; //used for Display of payroll_Items code
  $prolItemDrAccount[$a]    = $payroll_Items[$a]->debit_account_id; //used for Display of payroll_Items debit_account_id
  $prolItemCrAccount[$a]    = $payroll_Items[$a]->credit_account_id; //used for Display of payroll_Items credit_account_id
  $payroll_List .= $payroll_Items[$a]->id . "^^";
}

/*************************     
Fetch from projects table
 *************************/
$projects = Projects::find(
  [
    'conditions' => "id = ?",
    'bind' => [$this->project_id]
  ]

);
for ($a = 0; $a < count($projects); $a++) {
  if ($projects[$a]->id == '') {
    continue;
  }
  $projectValues[$a]    = $projects[$a]->id; //used for project id values
  $projectDisplays[$a]    = $projects[$a]->code; //used for Display of projects
  $pjList .= $projects[$a]->id . "^^";
}

/*************************     
Fetch from project_budgetline_refs table (Already fetched and ready-made array from the respective model class)
 *************************/
$PrjBgtLineArray = ProjectBudgetlineRefs::getPbrs();

/*************************     
Fetch from employees table
 *************************/
$employees = Employees::findAssignedEmployeesByProjectId($this->project_id);
for ($a = 0; $a < count($employees); $a++) {
  $employeeValues[$a]    = $employees[$a]->id;
  $employeeDisplays[$a]    =  $employees[$a]->staffName;
  $project_id_array[$a]    =  $employees[$a]->project_id;
  $pbr_id_array[$employees[$a]->id]    =  $employees[$a]->project_budgetline_ref_id;
  $rate_array[$employees[$a]->id]    =  $employees[$a]->rate;
  $tax_auth_array[$employees[$a]->id]    =  $employees[$a]->tax_auth_id;
  $empGrossIncomeArray[$employees[$a]->id] = '';
  if (!$empGrossIncomeArray[$employees[$a]->id]) {
    $empGrossIncomeArray[$employees[$a]->id] .= $employees[$a]->monthly_salary;
  }
  $empList .= $employees[$a]->id . "^^";
}

/*************************     
Fetch tax_auth(tax authority) options for form and assign to employee project
 *************************/
$taxAuthArray = TaxAuths::getOptionsForForm();

/*************************     
Fetch payroll_items options for form and assign to employee 
 *************************/
?>
<?php $this->start('head') ?>
<link rel="stylesheet" href="<?= SITE_ROOT ?>css/formstyles.css?v=<?= VERSION ?>">
<link rel="stylesheet" href="<?= SITE_ROOT ?>css/select2/css/select2.min.css">
<script src="<?= SITE_ROOT ?>css/select2/js/select2.full.min.js"></script>
<?php $this->end() ?>
<?php $this->start('body'); ?>
<div class="row">
  <div class="col-md-11">
    <div class="card border-mainColor bg-dark text-white text-uppercase font-weight-bold py-2">
      <div class="card-header bg-mainColor d-flex align-items-center justify-content-between text-white">
        <a class="btn btn-secondary btn-sm font-weight-bold " href="<?= SITE_ROOT ?>payrolls/index"><i class="fas fa-hand-point-left"></i> Back</a>
        <h6 class="font-weight-bold">Project Staff Assignment</h6>
      </div>
      <form action="<?= SITE_ROOT ?>payrolls/processprojectpayroll2" method="POST" class="card-body bg-secondary" id="mainForm">
        <div class="row bg-dark mb-1 justify-content-between">
          <?= FH::csrfInput(); ?>
          <?= FH::hiddenInput('payroll_List', rtrim($payroll_List, '^^')) ?>
          <?= FH::hiddenInput('pjList', rtrim($pjList, '^^')) ?>
          <?= FH::hiddenInput('empList', rtrim($empList, '^^')) ?>
        </div>
        <fieldset class="row bg-dark mx-0 my-5 d-flex" id="itemsGroup1Fieldset">
          <legend class="col-12 text-center  ">Employee Project budgetlines Section</legend>
          <div class="col-12 table-responsive">
            <table class="table table-sm table-striped table-bordered text-white" id="itemsGroup1Table">
              <thead class="bg-mainColor thead  ">
                <tr class="text-center">
                  <?php
                  // The +6 below represents the number of columns for the tr tds below i.e #,Employee Details,Budget Lines, Monthly Salary,%Allocation and Tax Jurisdiction
                  $colspan = (count($prolItemDisplays)) + 6;
                  for ($a = 0; $a < count($projectDisplays); $a++) : ?>
                    <th class="text-center bg-secondary" colspan=<?= $colspan ?>><?= $projectDisplays[$a] ?></th>
                  <?php endfor; ?>
                </tr>
                <tr class="text-capitalize">
                  <th>#</th>
                  <th>Employee Details</th>
                  <?php
                  for ($a = 0; $a < count($projectDisplays); $a++) : ?>
                    <th>BudgetLines </th>
                    <th>MonthlySalary </th>
                    <th>%Allocation</th>
                    <th>TaxJurisdiction</th>
                    <?php
                    for ($a = 0; $a < count($prolItemDisplays); $a++) : ?>
                      <th><?= $prolItemDisplays[$a] ?></th>
                    <?php endfor; ?>
                  <?php endfor; ?>
                </tr>

              </thead>
              <tbody class="text-capitalize">
                <?php
                // $prolItemsTotals = [];
                $prolItemsTotals = array_fill(0, count($prolItemDisplays), 0.00);

                for ($b = 0; $b < count($employeeDisplays); $b++) : ?>
                  <tr>
                    <td><?= number_format(($b + 1), 0, '.', ',') ?></td>
                    <td style="min-width: 120px;"><?= $employeeDisplays[$b] ?></td>
                    <td>
                      <?= FH::selectBlock('', "budgetLine_" . $employeeValues[$b], $pbr_id_array[$employeeValues[$b]], $PrjBgtLineArray[$project_id_array[$b]], ['class' => 'project_budgetline_ref_id', 'style' => 'width:120px', 'readonly' => 'readonly'], [], []) ?>
                    </td>
                    <td>
                      <?= FH::inputBlock('text', '', "monthly_salary_" . $employeeValues[$b], $empGrossIncomeArray[$employeeValues[$b]], ['class' => 'monthly_salary', 'style' => 'width:70px', 'readonly' => 'readonly'], [], []) ?>
                    </td>
                    <td>
                      <?= FH::inputBlock('text', '', "budgetLineAlloc_" . $employeeValues[$b], $rate_array[$employeeValues[$b]], ['class' => 'budgetlineAlloc', 'style' => 'width:50px', 'readonly' => 'readonly'], [], []) ?>
                    </td>
                    <td>
                      <?= FH::selectBlock('', "taxAuth_" . $employeeValues[$b], $tax_auth_array[$employeeValues[$b]], $taxAuthArray, ['class' => 'tax_auth_id', 'style' => 'width:120px', 'readonly' => 'readonly'], [], []) ?>
                    </td>
                    <!-- Payroll Items -->
                    <?php for ($c = 0; $c < count($prolItemDisplays); $c++) :
                      $amountValue = 0.00;
                      if (!empty($payrollDetailsArray[$employeeValues[$b]][$prolItemValues[$c]]['id'])) :
                        $amountValue = $payrollDetailsArray[$employeeValues[$b]][$prolItemValues[$c]]['amount'];
                    ?>
                        <td style="display:none;">
                          <?= FH::inputBlock('hidden', '', "id_" . $employeeValues[$b] . "_" . $prolItemValues[$c], $payrollDetailsArray[$employeeValues[$b]][$prolItemValues[$c]]['id'], ['class' => 'payroll_details'], [], []) ?>
                        </td>
                        <td> <?= FH::inputBlock('text', '', "amount_" . $employeeValues[$b] . "_" . $prolItemValues[$c], $amountValue, ['class' => 'amount', 'style' => 'width:70px'], [], []) ?>
                        </td>
                      <?php else :
                        $amountValue = Payrolls::calculateItemAmount($empGrossIncomeArray[$employeeValues[$b]], $rate_array[$employeeValues[$b]], $tax_auth_array[$employeeValues[$b]], $prolItemCodes[$c]);
                      ?>
                        <td style="display:none;">
                          <?= FH::inputBlock('hidden', '', "id_" . $employeeValues[$b] . "_" . $prolItemValues[$c], "", ['class' => 'payroll_details'], [], []) ?>
                        </td>
                        <td> <?= FH::inputBlock('text', '', "amount_" . $employeeValues[$b] . "_" . $prolItemValues[$c], $amountValue, ['class' => 'amount', 'style' => 'width:70px'], [], []) ?>
                        </td>
                    <?php endif;
                      $prolItemsTotals[$c] += $amountValue;
                    endfor; ?>
                  </tr>
                <?php endfor; ?>
                <tr>
                  <?php
                  $colspan = 6;
                  echo "<td class='text-center bg-secondary' colspan=$colspan></td>";
                  for ($d = 0; $d < count($prolItemDisplays); $d++) :
                  ?>
                    <td>
                      <?= FH::inputBlock('text', '', "total_" . $prolItemValues[$d], number_format($prolItemsTotals[$d], 2), ['class' => 'total_' . $prolItemValues[$d], 'style' => 'width:70px'], [], []) ?>
                    </td>
                  <?php
                  endfor;
                  ?>
                </tr>
              </tbody>
            </table>
          </div>
        </fieldset>
        <!-- Submit and Cancel Buttons Row -->
        <div class="row mt-5">
          <div class="col-md-12 text-center text-white">
            <a href="<?= SITE_ROOT ?>payrolls" class="btn btn-large btn-danger mr-5 px-5 text-uppercase font-weight-bold ">Cancel</a>
            <?= FH::submitTag('Save', ['class' => 'btn btn-large btn-info ml-5 px-5 text-uppercase font-weight-bold'], []); ?>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- javascript1 -->


<?php $this->end() ?>