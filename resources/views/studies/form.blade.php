@php
    /**
     * Echo study form, shown inside the dashboard. The form itself stays in its clinical
     * language (English, left-to-right) whatever the interface language is.
     *
     * @var \App\Models\EchoStudy|null $study  Set when editing a saved study.
     */
    $study ??= null;
    $editing = $study !== null;
    $amending = $editing && $study->isSigned();
    $title = $amending ? __('Amend echo study') : ($editing ? __('Edit echo study') : __('New echo study'));
@endphp

<x-dashboard-layout :title="$title">
    <link rel="stylesheet" href="/echo-form/persianDatepicker/css/persianDatepicker-default.css">
    <link rel="stylesheet" href="/echo-form/voice-input.css">
    <script src="/echo-form/persianDatepicker/js/jquery-1.10.1.min.js"></script>
    <script src="/echo-form/persianDatepicker/js/persianDatepicker.min.js"></script>
    <script src="/echo-form/voice-input.js" defer></script>
    @include('studies.partials.form-styles')

    <div id="echo-app" dir="ltr" lang="en">
    <div id="app-layout">
    <div id="form-container">
      <!-- Multi-Patient Tab Bar Toolbar -->
      @unless ($editing)
      <div class="patient-bar-wrapper">
        <div id="patientTabsContainer" class="patient-tabs-container"></div>
        <button type="button" class="add-patient-btn" onclick="addNewPatientTab()">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          <span>New Patient</span>
        </button>
      </div>
      @else
      <div id="patientTabsContainer" class="hidden"></div>
      @endunless

      <form id="echoForm" autocomplete="off">
      <input type="hidden" name="_token" value="{{ csrf_token() }}" />
      @if ($editing)
      <div class="edit-banner {{ $amending ? 'is-amending' : '' }}">
        <div class="edit-banner-head">
          <p class="edit-banner-title">
            {{ $amending ? 'Amending signed report' : 'Editing draft' }} #{{ $study->id }}
            — {{ $study->patient->first_name.' '.$study->patient->last_name }}
          </p>
          <a href="{{ route('studies.show', $study) }}" class="ef-btn ef-btn-secondary">{{ __('Back to report') }}</a>
        </div>
        @if ($amending)
        <div class="field">
          <label for="amendment_reason">Reason for amendment <span class="req">*</span></label>
          <textarea id="amendment_reason" name="amendment_reason" class="no-voice" required style="min-height:40px"></textarea>
        </div>
        @endif
      </div>
      @endif

    <div class="row">
      <div class="field">
        <label for="first_name">First Name <span class="req">*</span></label>
        <input id="first_name" name="first_name" type="text" required />
      </div>

      <div class="field">
        <label for="last_name">Last Name <span class="req">*</span></label>
        <input id="last_name" name="last_name" type="text" required />
      </div>

      <div class="field">
        <label for="nationalID">National ID <span class="req">*</span></label>
        <input id="nationalID" name="nationalID" type="text" required />
      </div>
      
      <div class="field">
        <label for="dob">Date of Birth</label>
        <input id="dob" name="dob" type="text" class="no-voice" />
        <span id="dob-display"></span>
      </div>


      <div class="field">
        <label for="phone_number">Phone Number</label>
        <input id="phone_number" name="phone_number" type="text" />
      </div>

      <div class="field">
        <label for="gender">Gender</label>
        <select id="gender" name="gender">
          <option value="">Select gender</option>
          <option value="Male">Male</option>
          <option value="Female">Female</option>
        </select>
      </div>

      <div class="field">
        <label for="Echo_indication">Echo indication</label>
        <select id="Echo_indication" name="Echo_indication">
          <option value="None">None</option>
          <option value="Chest pain">Chest pain</option>
          <option value="Dyspnea">Dyspnea</option>
          <option value="Palpitation">Palpitation</option>
          <option value="Other">Other</option>
        </select>
      </div>
      <div class="field">
        <label for="echo_date">Date of Echo</label>
        <input id="echo_date" name="echo_date" type="text" class="no-voice" />
        <span id="echo_date-display"></span>
      </div>
      <div class="field hidden" id="other_Echo_indication_wrap">
        <label for="other_Echo_indication">other Echo indication</label>
        <input id="other_Echo_indication" name="other_Echo_indication" type="text" />
      </div>

      <div class="field" style="flex:1 1 100%">
        <label for="History_diseases">History of diseases</label>
        <textarea id="History_diseases" name="History_diseases" style="min-height: 40px;"></textarea>
      </div>
    </div>



    <!-- Section Navigation Bar -->
    <div class="section-nav-wrapper">
      <div class="section-nav-container">
        <button type="button" class="section-nav-btn active" data-target="sec-patient-info" title="Patient information">
          <span class="btn-abbr">Info</span>
        </button>
        <button type="button" class="section-nav-btn" data-target="sec-lv" title="Left Ventricle">
          <span class="btn-abbr">LV</span>
        </button>
        <button type="button" class="section-nav-btn" data-target="sec-rv" title="Right Ventricle">
          <span class="btn-abbr">RV</span>
        </button>
        <button type="button" class="section-nav-btn" data-target="sec-la" title="Left Atrium">
          <span class="btn-abbr">LA</span>
        </button>
        <button type="button" class="section-nav-btn" data-target="sec-ra" title="Right Atrium">
          <span class="btn-abbr">RA</span>
        </button>
        <button type="button" class="section-nav-btn" data-target="sec-mv" title="Mitral Valve">
          <span class="btn-abbr">MV</span>
        </button>
        <button type="button" class="section-nav-btn" data-target="sec-av" title="Aortic Valve">
          <span class="btn-abbr">AV</span>
        </button>
        <button type="button" class="section-nav-btn" data-target="sec-pv" title="Pulmonary Valve">
          <span class="btn-abbr">PV</span>
        </button>
        <button type="button" class="section-nav-btn" data-target="sec-tv" title="Tricuspid Valve">
          <span class="btn-abbr">TV</span>
        </button>
        <button type="button" class="section-nav-btn" data-target="sec-ivc" title="Inferior vena cava & PAP">
          <span class="btn-abbr">IVC/PAP</span>
        </button>
        <button type="button" class="section-nav-btn" data-target="sec-peri" title="Pericardium">
          <span class="btn-abbr">Peri</span>
        </button>
        <button type="button" class="section-nav-btn" data-target="sec-notes" title="Notes">
          <span class="btn-abbr">Notes</span>
        </button>
      </div>
    </div>

    <div id="sec-patient-info" class="form-section active">
      <div class="form-section-header">Patient information</div>
      <div class="row" style="margin-top:8px">
        <div class="field"><label for="BP">BP (mmHg)</label><input id="BP" name="BP" type="text" />
        </div>
        <div class="field"><label for="HR">HR (beat/min)</label><input id="HR" name="HR" type="number" step="1" /></div>
        <div class="field"><label for="Height">Height (cm)</label><input id="Height" name="Height" type="number" />
        </div>

        <div class="field">
          <label for="Rhythm">Rhythm</label>
          <select id="Rhythm" name="Rhythm">
            <option value="None">None</option>
            <option value="Sinus">Sinus</option>
            <option value="AF">AF</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div class="field hidden" id="other_Rhythm_wrap">
          <label for="other_Rhythm">other Rhythm</label>
          <input id="other_Rhythm" name="other_Rhythm" type="text" />
        </div>

        <div class="field"><label for="O2_sat">O2 sat (%)</label><input id="O2_sat" name="O2_sat" type="number"
            step="1" /></div>
        <div class="field"><label for="Weight">Weight (kg)</label><input id="Weight" name="Weight" type="number"
            step="1" /></div>
        <div class="field">
          <label for="Echo_type">Echo type</label>
          <select id="Echo_type" name="Echo_type">
            <option value="None">None</option>
            <option value="Transthoracic echocardiography (TTE)">Transthoracic echocardiography (TTE)</option>
            <option value="Transesophageal echocardiography (TEE)">Transesophageal echocardiography (TEE)</option>
            <option value="Stress echocardiography">Stress echocardiography</option>
            <option value="Contrast echocardiography">Contrast echocardiography</option>
            <option value="Other">Other</option>
          </select>
        </div>

        <div class="field hidden" id="other_Echo_type_wrap">
          <label for="other_Echo_type">Other Echo type</label>
          <input id="other_Echo_type" name="other_Echo_type" type="text" />
        </div>
      </div>
    </div>

    <!-- Left ventricle -->
    <div id="sec-lv" class="form-section">
      <div class="form-section-header">Left Ventricle</div>
      <div class="row" style="margin-top:8px">
        <div class="field"><label for="lv_LVEDV">LVEDV (cc)</label><input id="lv_LVEDV" name="lv_LVEDV" type="number"
            step="0.1" /></div>
        <div class="field"><label for="lv_LVESV">LVESV (cc)</label><input id="lv_LVESV" name="lv_LVESV" type="number"
            step="0.1" /></div>
        <div class="field"><label for="lv_LVEDD">LVEDD (mm)</label><input id="lv_LVEDD" name="lv_LVEDD" type="number"
            step="0.1" /></div>
        <div class="field"><label for="lv_LVESD">LVESD (mm)</label><input id="lv_LVESD" name="lv_LVESD" type="number"
            step="0.1" /></div>
        <div class="field"><label for="lv_IVS_thickness">IVS thickness (mm)</label><input id="lv_IVS_thickness"
            name="lv_IVS_thickness" type="number" step="0.1" /></div>
        <div class="field"><label for="lv_Posterior">Posterior wall (mm)</label><input id="lv_Posterior"
            name="lv_Posterior" type="number" step="0.1" /></div>
        <div class="field"><label for="lv_EF">EF (%)</label><input id="lv_EF" name="lv_EF" type="number" step="0.1" />
        </div>
        <div class="field">
          <label for="lv_size">LV Size</label>
          <select id="lv_size" name="lv_size">
            <option value="Auto" selected>Auto</option>
            <option value="None">None</option>
            <option value="Normal LV size">Normal LV size</option>
            <option value="Mild LV enlargement">Mild LV enlargement</option>
            <option value="Mild to moderate LV enlargement">Mild to moderate LV enlargement</option>
            <option value="Moderate LV enlargement">Moderate LV enlargement</option>
            <option value="Moderate to severe LV enlargement">Moderate to severe LV enlargement</option>
            <option value="Severe LV enlargement">Severe LV enlargement</option>
            <option value="Manual">Manual</option>
          </select>
        </div>
        <div class="field">
          <label for="lv_Systolic">Systolic Function</label>
          <select id="lv_Systolic" name="lv_Systolic">
            <option value="Auto" selected>Auto</option>
            <option value="None">None</option>
            <option value="With normal systolic function">With normal systolic function</option>
            <option value="With mild systolic dysfunction">With mild systolic dysfunction</option>
            <option value="With mild to moderate systolic dysfunction">With mild to moderate systolic dysfunction
            </option>
            <option value="With moderate systolic dysfunction">With moderate systolic dysfunction</option>
            <option value="With moderate to severe systolic dysfunction">With moderate to severe systolic dysfunction
            </option>
            <option value="With severe systolic dysfunction">With severe systolic dysfunction</option>
          </select>
        </div>
        <div class="field">
          <label for="lv_Diastolic">Diastolic Function</label>
          <select id="lv_Diastolic" name="lv_Diastolic">
            <option value="Auto">Auto</option>
            <option value="None" selected>None</option>
            <option value="Normal diastolic function">Normal diastolic function</option>
            <option value="Grade I diastolic dysfunction">Grade I diastolic dysfunction</option>
            <option value="Grade II diastolic dysfunction">Grade II diastolic dysfunction</option>
            <option value="Grade III diastolic dysfunction">Grade III diastolic dysfunction</option>
            <option value="Indeterminate diastolic function">Indeterminate diastolic function</option>
          </select>
        </div>
        <div class="field">
          <label for="lv_Hypertrophy">Hypertrophy</label>
          <select id="lv_Hypertrophy" name="lv_Hypertrophy">
            <option value="Auto" selected>Auto</option>
            <option value="None">None</option>
            <option value="No LVH">No LVH</option>
            <option value="Mild concentric LVH">Mild concentric LVH</option>
            <option value="Moderate concentric LVH">Moderate concentric LVH</option>
            <option value="Severe concentric LVH">Severe concentric LVH</option>
            <option value="Asymmetric LVH with SAM and LVOT obstruction">Asymmetric LVH with SAM and LVOT obstruction
            </option>
            <option value="Asymmetric LVH without LVOT obstruction">Asymmetric LVH without LVOT obstruction</option>
          </select>
        </div>
        <div class="field">
          <label for="lv_clot">LV Clot</label>
          <select id="lv_clot" name="lv_clot">
            <option value="None" selected>None</option>
            <option value="No LV clot">No LV clot</option>
            <option value="Small mobile LV clot">Small mobile LV clot</option>
            <option value="Large mobile LV clot">Large mobile LV clot</option>
            <option value="Small organized LV clot">Small organized LV clot</option>
            <option value="Large organized LV clot">Large organized LV clot</option>
          </select>
        </div>
        <div class="field hidden" id="PPG_wrap">
          <label for="PPG">PPG (mmHg)</label>
          <input id="PPG" name="PPG" type="number" step="1" />
        </div>
      </div>

      <div class="row">
        <div class="field" id="lv_manual_row" style="display: none;">
          <label for="lv_size_manual">Manual LV Size Description</label>
          <input type="text" id="lv_size_manual" name="lv_size_manual" />
        </div>
      </div>

      <details>
        <summary>Tissue Doppler Imaging (TDI)</summary>
        <div class="row" style="margin-top:8px">
          <div class="field"><label for="lv_septale">Septal e' (cm/s)</label><input id="lv_septale" name="lv_septale"
              type="number" step="0.1" /></div>
          <div class="field"><label for="lv_lateral">Lateral e' (cm/s)</label><input id="lv_lateral" name="lv_lateral"
              type="number" step="0.1" /></div>
        </div>
        <div class="row">
          <div class="field"><label for="lv_septala">Septal a' (cm/s)</label><input id="lv_septala" name="lv_septala"
              type="number" step="0.1" /></div>
          <div class="field"><label for="lv_septal_Sm">Septal S' (cm/s)</label><input id="lv_septal_Sm"
              name="lv_septal_Sm" type="number" step="0.1" /></div>
        </div>
      </details>
      <details>
        <summary>LV Wall Motion</summary>
        <div style="margin-top:8px">
          <div style="display: flex; flex-direction: row; align-items: center; gap: 20px; margin-bottom: 10px; flex-wrap: wrap;">
            <label for="lv_rwma_checked" style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; margin: 0; user-select: none;">
              <input type="checkbox" id="lv_rwma_checked" name="lv_rwma_checked" value="1" style="margin: 0; width: auto;" />
              <span>Include in report</span>
            </label>
            <label for="lv_global_hypokinesia" style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; margin: 0; user-select: none;">
              <input type="checkbox" id="lv_global_hypokinesia" name="lv_global_hypokinesia" value="1" style="margin: 0; width: auto;" />
              <span>Global hypokinesia</span>
            </label>
          </div>
          <div class="small">Select status for each segment (Normal / Mild hypokinesia / Severe hypokinesia / Akinesia /
            Dyskinesia / Aneurysm)</div>
          <div id="rwma-segments"></div>

          <div class="row" style="margin-top:8px">
            <div class="field"><label for="lv_with_preserved">with</label>
              <select id="lv_with_preserved" name="lv_with">
                <option value="none">none</option>
                <option value="1">preserved tissue thickness</option>
                <option value="2">thinning and loss of tissue</option>
              </select>
            </div>
            <div class="field hidden" id="other_clot_wrap_lv"><label for="other_clot_lv">other clot</label><input
                id="lv_other_clot" name="lv_other_clot" /></div>
          </div>

        </div>
      </details>

      <div class="row" style="margin-top:8px">
        <div class="field" style="flex:1 1 100%"><label for="other_des_lv">Other descriptions</label><textarea
            id="other_des_lv" name="other_des_lv"></textarea></div>
      </div>
    </div>

    <!-- Right Ventricle -->
    <div id="sec-rv" class="form-section">
      <div class="form-section-header">Right Ventricle</div>
      <div class="row" style="margin-top:8px">
        <div class="field"><label for="rv_RV_basal_diameter">RV Basal Diameter (mm)</label><input
            id="rv_RV_basal_diameter" name="rv_RV_basal_diameter" type="number" step="0.1" /></div>
        <div class="field"><label for="rv_RV_mid_diameter">RV Mid Diameter (mm)</label><input id="rv_RV_mid_diameter"
            name="rv_RV_mid_diameter" type="number" step="0.1" /></div>
        <div class="field"><label for="rv_RV_longitudinal_diameter">RV Longitudinal Diameter (mm)</label><input
            id="rv_RV_longitudinal_diameter" name="rv_RV_longitudinal_diameter" type="number" step="0.1" /></div>

        <div class="field"><label for="rv_TAPSE">TAPSE (mm)</label><input id="rv_TAPSE" name="rv_TAPSE" type="number"
            step="0.1" /></div>
        <div class="field"><label for="rv_S_velocity">S' Velocity (cm/s)</label><input id="rv_S_velocity"
            name="rv_S_velocity" type="number" step="0.1" /></div>

        <div class="field">
          <label for="rv_size">RV Size</label>
          <select id="rv_size" name="rv_size">
            <option value="Auto" selected>Auto</option>
            <option value="None">None</option>
            <option value="Normal RV size">Normal RV size</option>
            <option value="Mild RV enlargement">Mild RV enlargement</option>
            <option value="Mild to moderate RV enlargement">Mild to moderate RV enlargement</option>
            <option value="Moderate RV enlargement">Moderate RV enlargement</option>
            <option value="Moderate to severe RV enlargement">Moderate to severe RV enlargement</option>
            <option value="Severe RV enlargement">Severe RV enlargement</option>
            <option value="Manual">Manual</option>
          </select>
        </div>

        <div class="field">
          <label for="rv_systolic">Systolic Function</label>
          <select id="rv_Systolic" name="rv_Systolic">
            <option value="Auto" selected>Auto</option>
            <option value="None">None</option>
            <option value="With normal systolic function">With normal systolic function</option>
            <option value="With mild systolic dysfunction">With mild systolic dysfunction</option>
            <option value="With mild to moderate systolic dysfunction">With mild to moderate systolic dysfunction
            </option>
            <option value="With moderate systolic dysfunction">With moderate systolic dysfunction</option>
            <option value="With moderate to severe systolic dysfunction">With moderate to severe systolic dysfunction
            </option>
            <option value="With severe systolic dysfunction">With severe systolic dysfunction</option>
          </select>
        </div>

        <div class="field">
          <label for="rv_Hypertrophy">Hypertrophy</label>
          <select id="rv_Hypertrophy" name="rv_Hypertrophy">
            <option value="Auto" selected>Auto</option>
            <option value="None">None</option>
            <option value="No RVH">No RVH</option>
            <option value="Mild RVH">Mild RVH</option>
            <option value="Moderate RVH">Moderate RVH</option>
            <option value="Severe RVH">Severe RVH</option>
            <option value="Other">Other</option>
          </select>
        </div>
      </div>

      <div class="row">
        <div class="field" id="rv_manual_row" style="display: none;">
          <label for="rv_size_manual">Manual RV Size Description</label>
          <input type="text" id="rv_size_manual" name="rv_size_manual" />
        </div>

        <div class="field" style="flex: 1 1 100%;">
          <label for="rv_other_des">Other Descriptions</label>
          <textarea id="rv_other_des" name="rv_other_des"></textarea>
        </div>
      </div>
    </div>

    <!-- Left Atrium -->
    <div id="sec-la" class="form-section">
      <div class="form-section-header">Left Atrium</div>
      <div class="row" style="margin-top:8px">
        <div class="field">
          <label for="la_size">LA Size</label>
          <select id="la_size" name="la_size">
            <option value="Auto" selected>Auto</option>
            <option value="None">None</option>
            <option value="Normal LA size">Normal LA size</option>
            <option value="Mild LA enlargement">Mild LA enlargement</option>
            <option value="Moderate LA enlargement">Moderate LA enlargement</option>
            <option value="Severe LA enlargement">Severe LA enlargement</option>
          </select>
        </div>
        <div class="field"><label for="la_area">LA Area (cm²)</label><input id="la_area" name="la_area" type="number"
            step="0.1" /></div>
        <div class="field"><label for="la_volume">LA Volume (cc)</label><input id="la_volume" name="la_volume"
            type="number" step="0.1" /></div>


        <div class="field"><label for="la_diameter">LA Diameter (mm)</label><input id="la_diameter" name="la_diameter"
            type="number" step="0.1" /></div>
        <div class="field">
          <label for="la_smoky">Smoky Appearance</label>
          <select id="la_smoky" name="la_smoky">
            <option value="-1">None</option>
            <option value="0">No</option>
            <option value="1">Yes</option>
          </select>
        </div>

        <div class="field">
          <label for="la_clot">LA Clot</label>
          <select id="la_clot" name="la_clot">
            <option value="-1">None</option>
            <option value="0">No LA clot</option>
            <option value="1">LA clot present</option>
          </select>
        </div>
        <div class="field" style="flex:1 1 100%">
          <label for="la_other_des">Other Descriptions</label>
          <textarea id="la_other_des" name="la_other_des"></textarea>
        </div>
      </div>
    </div>

    <!-- Right Atrium -->
    <div id="sec-ra" class="form-section">
      <div class="form-section-header">Right Atrium</div>
      <div class="row" style="margin-top:8px">
        <div class="field">
          <label for="ra_size">RA Size</label>
          <select id="ra_size" name="ra_size">
            <option value="Auto" selected>Auto</option>
            <option value="None">None</option>
            <option value="Normal RA size">Normal RA size</option>
            <option value="Mild RA enlargement">Mild RA enlargement</option>
            <option value="Moderate RA enlargement">Moderate RA enlargement</option>
            <option value="Severe RA enlargement">Severe RA enlargement</option>
          </select>
        </div>
        <div class="field"><label for="ra_area">RA Area (cm²)</label><input id="ra_area" name="ra_area" type="number"
            step="0.1" /></div>
        <div class="field"><label for="ra_volume">RA Volume (cc)</label><input id="ra_volume" name="ra_volume"
            type="number" step="0.1" /></div>

        <div class="field" style="flex:1 1 100%">
          <label for="ra_other_des">Other Descriptions</label>
          <textarea id="ra_other_des" name="ra_other_des"></textarea>
        </div>
      </div>
    </div>

    <!-- Mitral Valve -->
    <div id="sec-mv" class="form-section">
      <div class="form-section-header">Mitral Valve</div>
      <div class="row" style="margin-top:8px">
        <div class="field valve-status-field">
          <label for="mv_mvls">Mitral Valve</label>
          <select id="mv_mvls" name="mv_mvls">
            <option value="Auto" selected>Auto</option>
            <option value="None">None</option>
            <option value="Normal">Normal</option>
            <option value="Abnormal">Abnormal</option>
          </select>
        </div>
        <div class="field" id="mv_abnormality_container" style="display: none;">
          <label for="mv_abnormality_description">Abnormality Description</label>
          <input id="mv_abnormality_description" name="mv_abnormality_description" type="text" />
        </div>

        <div class="field">
          <label for="mv_PE_Velocity">Peak E Velocity (m/s)</label>
          <input id="mv_PE_Velocity" name="mv_PE_Velocity" type="number" step="0.1" />
        </div>
        <div class="field">
          <label for="mv_PA_Velocity">Peak A Velocity (m/s)</label>
          <input id="mv_PA_Velocity" name="mv_PA_Velocity" type="number" step="0.1" />
        </div>
        <div class="field">
          <label for="mv_stenosis1">Stenosis</label>
          <select id="mv_stenosis1" name="mv_stenosis1">
            <option value="None">None</option>
            <option value="No MS">No MS</option>
            <option value="Mild MS">Mild MS</option>
            <option value="Moderate MS">Moderate MS</option>
            <option value="Severe MS">Severe MS</option>
          </select>
        </div>
        <div class="field">
          <label for="mv_regurgitant">Regurgitation</label>
          <select id="mv_regurgitant" name="mv_regurgitant">
            <option value="None">None</option>
            <option value="No MR">No MR</option>
            <option value="Trivial MR">Trivial MR</option>
            <option value="Mild MR">Mild MR</option>
            <option value="Mild to Moderate MR">Mild to Moderate MR</option>
            <option value="Moderate MR">Moderate MR</option>
            <option value="Moderate to Severe MR">Moderate to Severe MR</option>
            <option value="Severe MR">Severe MR</option>
          </select>
        </div>

        <div class="field" style="flex: 1 1 100%;">
          <label for="mv_other_des">Other Descriptions</label>
          <textarea id="mv_other_des" name="mv_other_des"></textarea>
        </div>
      </div>
    </div>

    <!-- Aortic Valve -->
    <div id="sec-av" class="form-section">
      <div class="form-section-header">Aortic Valve</div>
      <div class="row" style="margin-top:8px">
        <div class="field valve-status-field">
          <label for="av_Aortic_valve">Aortic Valve</label>
          <select id="av_Aortic_valve" name="av_Aortic_valve">
            <option value="Auto" selected>Auto</option>
            <option value="None">None</option>
            <option value="Normal">Normal</option>
            <option value="Abnormal">Abnormal</option>
          </select>
        </div>
        <div class="field" id="av_abnormality_container" style="display: none;">
          <label for="av_abnormality_description">Abnormality Description</label>
          <input id="av_abnormality_description" name="av_abnormality_description" type="text" />
        </div>

        <div class="field">
          <label for="av_peakvelocity">Peak Velocity (m/s)</label>
          <input id="av_peakvelocity" name="av_peakvelocity" type="number" step="0.1" />
        </div>
        <div class="field">
          <label for="av_stenosis1">Stenosis</label>
          <select id="av_stenosis1" name="av_stenosis1">
            <option value="None">None</option>
            <option value="No AS">No AS</option>
            <option value="Mild AS">Mild AS</option>
            <option value="Moderate AS">Moderate AS</option>
            <option value="Severe AS">Severe AS</option>
          </select>
        </div>
        <div class="field">
          <label for="av_regurgitant">Regurgitation</label>
          <select id="av_regurgitant" name="av_regurgitant">
            <option value="None">None</option>
            <option value="No AR">No AR</option>
            <option value="Trivial AR">Trivial AR</option>
            <option value="Mild AR">Mild AR</option>
            <option value="Mild to Moderate AR">Mild to Moderate AR</option>
            <option value="Moderate AR">Moderate AR</option>
            <option value="Moderate to Severe AR">Moderate to Severe AR</option>
            <option value="Severe AR">Severe AR</option>
          </select>
        </div>

        <div class="field" style="flex: 1 1 100%;">
          <label for="av_other_des">Other Descriptions</label>
          <textarea id="av_other_des" name="av_other_des"></textarea>
        </div>
      </div>

      <!-- Aorta Subsection -->
      <div class="subsection">
        <div class="subsection-title">Aorta</div>
        <div class="row">
          <div class="field">
            <label for="av_aorta">Aorta</label>
            <select id="av_aorta" name="av_aorta">
              <option value="Auto" selected>Auto</option>
              <option value="None">None</option>
              <option value="Normal aortic size">Normal aortic size</option>
              <option value="Dilated aortic size">Dilated aortic size</option>
              <option value="Manual">Manual</option>
            </select>
          </div>
          <div class="field" id="av_aorta_manual_row" style="display: none;">
            <label for="av_aorta_manual">Manual Aorta Description</label>
            <input type="text" id="av_aorta_manual" name="av_aorta_manual" />
          </div>
          <div class="field">
            <label for="av_annulus">Annulus (mm)</label>
            <input id="av_annulus" name="av_annulus" type="number" step="0.1" />
          </div>
          <div class="field">
            <label for="av_sinus_valsalva">Sinuses of Valsalva (mm)</label>
            <input id="av_sinus_valsalva" name="av_sinus_valsalva" type="number" step="0.1" />
          </div>
          <div class="field">
            <label for="av_st_junction">Sinotubular Junction (mm)</label>
            <input id="av_st_junction" name="av_st_junction" type="number" step="0.1" />
          </div>
          <div class="field">
            <label for="av_proximal_ascending_aorta">Proximal Ascending Aorta (mm)</label>
            <input id="av_proximal_ascending_aorta" name="av_proximal_ascending_aorta" type="number" step="0.1" />
          </div>
        </div>
      </div>
    </div>

    <!-- Pulmonary Valve -->
    <div id="sec-pv" class="form-section">
      <div class="form-section-header">Pulmonary Valve</div>
      <div class="row" style="margin-top:8px">
        <div class="field valve-status-field">
          <label for="pv_Pulmonary_valve">Pulmonary Valve</label>
          <select id="pv_Pulmonary_valve" name="pv_Pulmonary_valve">
            <option value="Auto" selected>Auto</option>
            <option value="None">None</option>
            <option value="Normal">Normal</option>
            <option value="Abnormal">Abnormal</option>
          </select>
        </div>
        <div class="field" id="pv_abnormality_container" style="display: none;">
          <label for="pv_abnormality_description">Abnormality Description</label>
          <input id="pv_abnormality_description" name="pv_abnormality_description" type="text" />
        </div>

        <div class="field">
          <label for="pv_stenosis1">Stenosis</label>
          <select id="pv_stenosis1" name="pv_stenosis1">
            <option value="None">None</option>
            <option value="No PS">No PS</option>
            <option value="Mild PS">Mild PS</option>
            <option value="Moderate PS">Moderate PS</option>
            <option value="Severe PS">Severe PS</option>
          </select>
        </div>
        <div class="field">
          <label for="pv_PV_velocity">PV Velocity (m/s)</label>
          <input id="pv_PV_velocity" name="pv_PV_velocity" type="number" step="0.1" />
        </div>
        <div class="field">
          <label for="pv_PV_ACCT">ACCT (msec)</label>
          <input id="pv_PV_ACCT" name="pv_PV_ACCT" type="number" step="1" />
        </div>
        <div class="field">
          <label for="pv_regurgitant">Regurgitation</label>
          <select id="pv_regurgitant" name="pv_regurgitant">
            <option value="None">None</option>
            <option value="No PR">No PR</option>
            <option value="Trivial PR">Trivial PR</option>
            <option value="Mild PR">Mild PR</option>
            <option value="Mild to Moderate PR">Mild to Moderate PR</option>
            <option value="Moderate PR">Moderate PR</option>
            <option value="Moderate to Severe PR">Moderate to Severe PR</option>
            <option value="Severe PR">Severe PR</option>
          </select>
        </div>

        <div class="field" style="flex: 1 1 100%;">
          <label for="pv_other_des">Other Descriptions</label>
          <textarea id="pv_other_des" name="pv_other_des"></textarea>
        </div>
      </div>
    </div>

    <!-- Tricuspid Valve -->
    <div id="sec-tv" class="form-section">
      <div class="form-section-header">Tricuspid Valve</div>
      <div class="row" style="margin-top:8px">
        <div class="field valve-status-field">
          <label for="tv_Tricuspid_valve">Tricuspid Valve</label>
          <select id="tv_Tricuspid_valve" name="tv_Tricuspid_valve">
            <option value="Auto" selected>Auto</option>
            <option value="None">None</option>
            <option value="Normal">Normal</option>
            <option value="Abnormal">Abnormal</option>
          </select>
        </div>
        <div class="field" id="tv_abnormality_container" style="display: none;">
          <label for="tv_abnormality_description">Abnormality Description</label>
          <input id="tv_abnormality_description" name="tv_abnormality_description" type="text" />
        </div>

        <div class="field">
          <label for="tv_PE_Velocity">Peak E Velocity (m/s)</label>
          <input id="tv_PE_Velocity" name="tv_PE_Velocity" type="number" step="0.1" />
        </div>
        <div class="field">
          <label for="tv_PA_Velocity">Peak A Velocity (m/s)</label>
          <input id="tv_PA_Velocity" name="tv_PA_Velocity" type="number" step="0.1" />
        </div>
        <div class="field">
          <label for="tv_stenosis1">Stenosis</label>
          <select id="tv_stenosis1" name="tv_stenosis1">
            <option value="None">None</option>
            <option value="No TS">No TS</option>
            <option value="Mild TS">Mild TS</option>
            <option value="Moderate TS">Moderate TS</option>
            <option value="Severe TS">Severe TS</option>
          </select>
        </div>
        <div class="field">
          <label for="tv_regurgitant">Regurgitation</label>
          <select id="tv_regurgitant" name="tv_regurgitant">
            <option value="None">None</option>
            <option value="No TR">No TR</option>
            <option value="Trivial TR">Trivial TR</option>
            <option value="Mild TR">Mild TR</option>
            <option value="Mild to Moderate TR">Mild to Moderate TR</option>
            <option value="Moderate TR">Moderate TR</option>
            <option value="Moderate to Severe TR">Moderate to Severe TR</option>
            <option value="Severe TR">Severe TR</option>
          </select>
        </div>
        <div class="field">
          <label for="tv_TRG">TR Gradient (mmHg)</label>
          <input id="tv_TRG" name="tv_TRG" type="number" step="0.1" />
        </div>

        <div class="field" style="flex: 1 1 100%;">
          <label for="tv_other_des">Other Descriptions</label>
          <textarea id="tv_other_des" name="tv_other_des"></textarea>
        </div>
      </div>
    </div>

    <!-- IVC & PAP, Pericardium, Findings, Summary -->
    <div id="sec-ivc" class="form-section">
      <div class="form-section-header">Inferior vena cava & PAP</div>
      <div class="row" style="margin-top:8px">
        <div class="field"><label for="cp_ivc1">IVC</label>
          <select id="cp_ivc1" name="cp_ivc1">
            <option value="auto" selected>auto</option>
            <option value="">none</option>
            <option value="normal size">normal size</option>
            <option value="Dilated">Dilated</option>
          </select>
        </div>
        <div class="field"><label for="cp_ivc_size">IVC size (mm)</label><input id="cp_ivc_size" name="cp_ivc_size"
            type="number" step="0.01" /></div>
        <div class="field"><label for="cp_Respiratory_variation">Respiratory variation</label>
          <select id="cp_Respiratory_variation" name="cp_Respiratory_variation">
            <option value="">none</option>
            <option value="no respiratory variation">no respiratory variation</option>
            <option value="<50% respiratory variation">&lt;50% respiratory variation</option>
            <option value=">50% respiratory variation">&gt;50% respiratory variation</option>
          </select>
        </div>

        <div class="field"><label for="cp_quantity">Quantity</label>
          <select id="cp_quantity" name="cp_quantity">
            <option value="Auto" selected>Auto</option>
            <option value="">none</option>
            <option value="Normal PAP">Normal PAP</option>
            <option value="Top normal PAP">Top normal PAP</option>
            <option value="Mild PH">Mild PH</option>
            <option value="Moderate PH">Moderate PH</option>
            <option value="Severe PH">Severe PH</option>
          </select>
        </div>
        <div class="field"><label for="cp_SPAP">SPAP (mmHg)</label><input id="cp_SPAP" name="cp_SPAP" type="number"
            step="0.01" placeholder="Auto" /></div>
        <div class="field" style="flex:1 1 100%"><label for="cp_SPAP_des">SPAP descriptions</label><textarea
            id="cp_SPAP_des" name="cp_SPAP_des"></textarea></div>
      </div>
    </div>

    <!-- Pericardium -->
    <div id="sec-peri" class="form-section">
      <div class="form-section-header">Pericardium</div>
      <div class="row" style="margin-top:8px">
        <div class="field">
          <label for="pre_Morphology">Morphology</label>
          <select id="pre_Morphology" name="pre_Morphology">
            <option value="Normal pericardium" selected>Normal pericardium</option>
            <option value="">none</option>
            <option value="Thickened pericardium">Thickened pericardium</option>
            <option value="Calcified pericardium">Calcified pericardium</option>
            <option value="Thick and calcified pericardium">Thick and calcified pericardium</option>
          </select>
        </div>

        <div class="field">
          <label for="pre_Pericardial_effusion">Pericardial effusion</label>
          <select id="pre_Pericardial_effusion" name="pre_Pericardial_effusion">
            <option value="No PE" selected>No PE</option>
            <option value="">none</option>
            <option value="Small PE">Small PE</option>
            <option value="Moderate PE">Moderate PE</option>
            <option value="Large PE">Large PE</option>
          </select>
        </div>

        <div class="field">
          <label for="pre_quantity_PE">Quantity</label>
          <input id="pre_quantity_PE" name="pre_quantity_PE" type="text" />
        </div>

        <div class="field">
          <label for="pre_Hemodynamic">Hemodynamic</label>
          <select id="pre_Hemodynamic" name="pre_Hemodynamic">
            <option value="">Select hemodynamic status</option>
            <option value="with hemodynamic effect">with hemodynamic effect</option>
            <option value="without hemodynamic effect">without hemodynamic effect</option>
          </select>
        </div>

        <div class="field" style="flex:1 1 100%"><label for="pre_Pericardium_others">Other descriptions</label><textarea
            id="pre_Pericardium_others" name="pre_Pericardium_others"></textarea></div>
      </div>
    </div>

    <div id="sec-notes" class="form-section">
      <div class="form-section-header">Notes</div>
      <div class="row" style="margin-top:8px">
        <div class="field" style="flex:1 1 100%"><label for="finding">Additional findings</label><textarea id="finding"
            name="finding" maxlength="500" style="min-height: 40px;"></textarea></div>
        <div class="field" style="flex:1 1 100%">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <label for="summary" style="margin-bottom: 0;">Summary</label>
            <div style="display: flex; gap: 6px;">
              <button type="button" id="generateSummaryBtn" class="ef-btn-mini ef-btn-mini-accent" title="Auto-generate structured summary from form data">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>
                </svg>
                <span>Auto Summary</span>
              </button>
              <button type="button" id="clearSummaryBtn" class="ef-btn-mini" title="Clear summary text">Clear</button>
            </div>
          </div>
          <textarea id="summary" name="summary" maxlength="1500" style="min-height: 100px; line-height: 1.4;"></textarea>
        </div>
        <div class="field" style="flex:1 1 100%"><label for="recommendation">Recommendation</label><textarea id="recommendation" name="recommendation"
            maxlength="500" style="min-height: 40px;"></textarea></div>
      </div>
    </div>

    <div class="row" style="margin-top:8px">
      <div class="field">
        <label for="doctor">Doctor</label>
        <input id="doctor" name="doctor" type="text" list="doctors_list" />
        <datalist id="doctors_list"></datalist>
      </div>
    </div>

    <div class="controls">
      <button type="button" id="previewToggleBtn" class="ef-btn ef-btn-secondary preview-toggle" aria-pressed="false">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
          <circle cx="12" cy="12" r="3"/>
        </svg>
        <span>Live Preview</span>
      </button>
      <button type="reset" class="ef-btn ef-btn-secondary">Reset</button>
      <button type="button" id="saveBtn" class="ef-btn ef-btn-secondary {{ $editing ? 'hidden' : '' }}">Save Locally</button>
      <button type="button" id="submitBtn" class="ef-btn ef-btn-primary">{{ $amending ? 'Save Amendment' : ($editing ? 'Save Changes' : 'Save to Database') }}</button>
    </div>

  </form>
</div>

<div id="preview-resizer" class="hidden no-print"></div>

<div id="preview-container" class="hidden">
  <div class="preview-header no-print">
    <div class="preview-title-group">
      <svg class="preview-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
        <circle cx="12" cy="12" r="3"/>
      </svg>
      <span class="preview-title">Live Report Preview</span>
      <span class="preview-status-pill">
        <span class="preview-status-dot"></span>
        Live Sync
      </span>
    </div>
    <div class="preview-actions">
      <button type="button" class="preview-btn" id="previewRefreshBtn" title="Force Refresh">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
        </svg>
      </button>
      <button type="button" class="preview-btn" id="previewNewTabBtn" title="Open Report in New Tab">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14 21 3"/>
        </svg>
      </button>
      <button type="button" class="preview-btn close-btn" id="previewCloseBtn" title="Close Preview">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>
  </div>
  <iframe id="preview-iframe" name="preview-iframe" title="Live Report Preview"></iframe>
</div>
</div>

  <!-- Persian Custom Modal Dialog -->
  <dialog id="customAlertModal" class="custom-modal">
    <div class="modal-content">
      <div id="modalIcon" class="modal-icon"></div>
      <h3 id="modalTitle" class="modal-title"></h3>
      <div id="modalMessage" class="modal-message"></div>
      <div class="modal-actions">
        <button type="button" id="modalCloseBtn" class="modal-btn"></button>
      </div>
    </div>
  </dialog>
    </div>

    @include('studies.partials.form-script')
</x-dashboard-layout>
