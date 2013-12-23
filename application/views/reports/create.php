
<center>
<form id="create_advanced" action="http://localhost/reports/generate" method="post">
  <fieldset>
    <legend>Advanced Report</legend>
    <label> Starting System: </label>
        <select name="fromsystem">
            <?php foreach($systems->result() as $system) { ?>
              <option value="<?php echo"$system->solarSystemName";?>">  <?php echo"$system->solarSystemName"; ?> </option>
            <?php } ?>
        </select>
        <input type="checkbox" name="aordertype" value="buy"> Use Buy Orders </input>
    <br />

    <label> Finish System: </label>
        <select name="tosystem">
            <?php foreach($systems->result() as $system) { ?>
              <option value="<?php echo"$system->solarSystemName";?>">  <?php echo"$system->solarSystemName"; ?> </option>
            <?php } ?>
        </select>
        <input type="checkbox" name="sordertype" value="buy"> Use Buy Orders </input>
    <br />

    <label> Min Volume: </label>
    <input type="number" min="0" value="50" name="minvol">

    <br />

    <label> Min Margin: </label>
    <input type="number" min="0" value="50" name="minmargin">

    <br />
    <label>Min Profit:</label>
    <input type="number" min="0" value="250000" name="minprofit">

    <div class="form-actions">
    <button type="submit" class="btn-mini btn-primary"> Generate Report </button>
    </div>

    <br />
    <hr />

  </fieldset>
</form>
    </center>