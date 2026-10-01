<?php

namespace App\Services;

/**
 * Derived values for an echo report: BSA and indexed volumes, automatic
 * grading of chamber size/function, SPAP from TRG + estimated RAP, diastolic
 * function grading and aortic dilation.
 *
 * Extracted verbatim from the legacy view_report.php (which view_echo.php
 * duplicated with drift). A field left empty or set to "Auto" on the form is
 * filled in here; for each such field "<field>_was_auto", "_input_used" and
 * "_input_value" record how, for the tooltips on the report.
 */
class EchoCalculations
{
    /**
     * @param  array<string, mixed>  $report  A flat report row (see EchoStudy::toReportArray()).
     * @return array<string, mixed>
     */
    public static function derive(array $report): array
    {
        $height = isset($report['Height']) && is_numeric($report['Height']) ? (float) $report['Height'] : 0;
        $weight = isset($report['Weight']) && is_numeric($report['Weight']) ? (float) $report['Weight'] : 0;

        $bsa = 0;
        if ($height > 0 && $weight > 0) {
            $bsa = sqrt(($height * $weight) / 3600);
        }

        $lvedv = isset($report['lv_LVEDV']) && is_numeric($report['lv_LVEDV']) ? (float) $report['lv_LVEDV'] : 0;
        $lvedvi = 0;
        if ($lvedv > 0 && $bsa > 0) {
            $lvedvi = $lvedv / $bsa;
        }

        $lvdd = isset($report['lv_LVEDD']) && is_numeric($report['lv_LVEDD']) ? (float) $report['lv_LVEDD'] : 0;
        $lveddi = 0;
        if ($lvdd > 0 && $bsa > 0) {
            $lveddi = $lvdd / $bsa;
        }

        $report['computed_BSA'] = $bsa;
        $report['computed_LVEDVI'] = $lvedvi;
        $report['computed_LVEDDI'] = $lveddi;

        $report['lv_size_was_auto'] = false;
        $report['lv_size_input_used'] = '';
        $report['lv_size_input_value'] = '';
        $report['lv_Systolic_was_auto'] = false;
        $report['lv_Systolic_input_used'] = '';
        $report['lv_Systolic_input_value'] = '';
        $report['lv_Hypertrophy_was_auto'] = false;
        $report['lv_Hypertrophy_input_used'] = '';
        $report['lv_Hypertrophy_input_value'] = '';
        $report['lv_Diastolic_was_auto'] = false;
        $report['lv_Diastolic_input_used'] = '';
        $report['lv_Diastolic_input_value'] = '';
        $report['rv_size_was_auto'] = false;
        $report['rv_size_input_used'] = '';
        $report['rv_size_input_value'] = '';
        $report['rv_Systolic_was_auto'] = false;
        $report['rv_Systolic_input_used'] = '';
        $report['rv_Systolic_input_value'] = '';
        $report['la_size_was_auto'] = false;
        $report['la_size_input_used'] = '';
        $report['la_size_input_value'] = '';
        $report['ra_size_was_auto'] = false;
        $report['ra_size_input_used'] = '';
        $report['ra_size_input_value'] = '';
        $report['cp_SPAP_was_auto'] = false;
        $report['cp_SPAP_input_used'] = '';
        $report['cp_SPAP_input_value'] = '';
        $report['cp_quantity_was_auto'] = false;
        $report['cp_quantity_input_used'] = '';
        $report['cp_quantity_input_value'] = '';
        $report['av_aorta_was_auto'] = false;
        $report['av_aorta_dilated_segments'] = [];

        if (empty($report['lv_size']) || strcasecmp($report['lv_size'], 'Auto') === 0) {
            $isOriginalAuto = (strcasecmp($report['lv_size'] ?? '', 'Auto') === 0);
            $gender = isset($report['gender']) ? trim($report['gender']) : '';
            $auto_lv_size = null;
            $isMale = (strcasecmp($gender, 'Male') === 0 || $gender === '1');
            $isFemale = (strcasecmp($gender, 'Female') === 0 || $gender === '2');

            if ($isMale || $isFemale) {
                if ($lvedvi > 0) {
                    $report['lv_size_was_auto'] = true;
                    $report['lv_size_input_used'] = 'LVEDVI';
                    $report['lv_size_input_value'] = round($lvedvi, 1);
                    if ($isMale) {
                        if ($lvedvi <= 74) {
                            $auto_lv_size = 'Normal LV size';
                        } elseif ($lvedvi <= 89) {
                            $auto_lv_size = 'Mild LV enlargement';
                        } elseif ($lvedvi <= 100) {
                            $auto_lv_size = 'Moderate LV enlargement';
                        } else {
                            $auto_lv_size = 'Severe LV enlargement';
                        }
                    } else {
                        if ($lvedvi <= 61) {
                            $auto_lv_size = 'Normal LV size';
                        } elseif ($lvedvi <= 70) {
                            $auto_lv_size = 'Mild LV enlargement';
                        } elseif ($lvedvi <= 80) {
                            $auto_lv_size = 'Moderate LV enlargement';
                        } else {
                            $auto_lv_size = 'Severe LV enlargement';
                        }
                    }
                } elseif ($lvedv > 0) {
                    $report['lv_size_was_auto'] = true;
                    $report['lv_size_input_used'] = 'LVEDV';
                    $report['lv_size_input_value'] = $lvedv;
                    if ($isMale) {
                        if ($lvedv <= 150) {
                            $auto_lv_size = 'Normal LV size';
                        } elseif ($lvedv <= 174) {
                            $auto_lv_size = 'Mild LV enlargement';
                        } elseif ($lvedv <= 200) {
                            $auto_lv_size = 'Moderate LV enlargement';
                        } else {
                            $auto_lv_size = 'Severe LV enlargement';
                        }
                    } else {
                        if ($lvedv <= 106) {
                            $auto_lv_size = 'Normal LV size';
                        } elseif ($lvedv <= 120) {
                            $auto_lv_size = 'Mild LV enlargement';
                        } elseif ($lvedv <= 130) {
                            $auto_lv_size = 'Moderate LV enlargement';
                        } else {
                            $auto_lv_size = 'Severe LV enlargement';
                        }
                    }
                } elseif ($lveddi > 0) {
                    $report['lv_size_was_auto'] = true;
                    $report['lv_size_input_used'] = 'LVEDDI';
                    $report['lv_size_input_value'] = round($lveddi, 1);
                    if ($isMale) {
                        if ($lveddi <= 30) {
                            $auto_lv_size = 'Normal LV size';
                        } elseif ($lveddi <= 33) {
                            $auto_lv_size = 'Mild LV enlargement';
                        } elseif ($lveddi <= 36) {
                            $auto_lv_size = 'Moderate LV enlargement';
                        } else {
                            $auto_lv_size = 'Severe LV enlargement';
                        }
                    } else {
                        if ($lveddi <= 31) {
                            $auto_lv_size = 'Normal LV size';
                        } elseif ($lveddi <= 34) {
                            $auto_lv_size = 'Mild LV enlargement';
                        } elseif ($lveddi <= 37) {
                            $auto_lv_size = 'Moderate LV enlargement';
                        } else {
                            $auto_lv_size = 'Severe LV enlargement';
                        }
                    }
                } elseif ($lvdd > 0) {
                    $report['lv_size_was_auto'] = true;
                    $report['lv_size_input_used'] = 'LVEDD';
                    $report['lv_size_input_value'] = $lvdd;
                    if ($isMale) {
                        if ($lvdd <= 58) {
                            $auto_lv_size = 'Normal LV size';
                        } elseif ($lvdd <= 63) {
                            $auto_lv_size = 'Mild LV enlargement';
                        } elseif ($lvdd <= 68) {
                            $auto_lv_size = 'Moderate LV enlargement';
                        } else {
                            $auto_lv_size = 'Severe LV enlargement';
                        }
                    } else {
                        if ($lvdd <= 52) {
                            $auto_lv_size = 'Normal LV size';
                        } elseif ($lvdd <= 56) {
                            $auto_lv_size = 'Mild LV enlargement';
                        } elseif ($lvdd <= 61) {
                            $auto_lv_size = 'Moderate LV enlargement';
                        } else {
                            $auto_lv_size = 'Severe LV enlargement';
                        }
                    }
                }
            }

            if ($auto_lv_size !== null) {
                $report['lv_size'] = $auto_lv_size;
            } elseif ($isOriginalAuto) {
                $report['lv_size'] = '';
            }
        }

        // Auto-calculate lv_Systolic if not entered by user (i.e. empty, or "Auto")
        if (empty($report['lv_Systolic']) || strcasecmp($report['lv_Systolic'], 'Auto') === 0) {
            $isOriginalAuto = (strcasecmp($report['lv_Systolic'] ?? '', 'Auto') === 0);
            $ef = isset($report['lv_EF']) && is_numeric($report['lv_EF']) ? (float) $report['lv_EF'] : 0;
            $gender = isset($report['gender']) ? trim($report['gender']) : '';
            $isMale = (strcasecmp($gender, 'Male') === 0 || $gender === '1');
            $isFemale = (strcasecmp($gender, 'Female') === 0 || $gender === '2');

            $auto_systolic = null;
            if ($ef > 0 && ($isMale || $isFemale)) {
                $report['lv_Systolic_was_auto'] = true;
                $report['lv_Systolic_input_used'] = 'EF';
                $report['lv_Systolic_input_value'] = $ef;
                if ($isMale) {
                    if ($ef < 30) {
                        $auto_systolic = 'With severe systolic dysfunction';
                    } elseif ($ef < 41) {
                        $auto_systolic = 'With moderate systolic dysfunction';
                    } elseif ($ef < 52) {
                        $auto_systolic = 'With mild systolic dysfunction';
                    } else {
                        $auto_systolic = 'With normal systolic function';
                    }
                } else {
                    if ($ef < 30) {
                        $auto_systolic = 'With severe systolic dysfunction';
                    } elseif ($ef < 41) {
                        $auto_systolic = 'With moderate systolic dysfunction';
                    } elseif ($ef < 54) {
                        $auto_systolic = 'With mild systolic dysfunction';
                    } else {
                        $auto_systolic = 'With normal systolic function';
                    }
                }
            }

            if ($auto_systolic !== null) {
                $report['lv_Systolic'] = $auto_systolic;
            } elseif ($isOriginalAuto) {
                $report['lv_Systolic'] = '';
            }
        }

        // Auto-calculate lv_Hypertrophy if not entered by user (i.e. empty, or "Auto")
        if (empty($report['lv_Hypertrophy']) || strcasecmp($report['lv_Hypertrophy'], 'Auto') === 0) {
            $isOriginalAuto = (strcasecmp($report['lv_Hypertrophy'] ?? '', 'Auto') === 0);
            $ivs = isset($report['lv_IVS_thickness']) && is_numeric($report['lv_IVS_thickness']) ? (float) $report['lv_IVS_thickness'] : 0;
            $gender = isset($report['gender']) ? trim($report['gender']) : '';
            $isMale = (strcasecmp($gender, 'Male') === 0 || $gender === '1');
            $isFemale = (strcasecmp($gender, 'Female') === 0 || $gender === '2');

            $auto_hypertrophy = null;
            if ($ivs > 0 && ($isMale || $isFemale)) {
                $report['lv_Hypertrophy_was_auto'] = true;
                $report['lv_Hypertrophy_input_used'] = 'IVS';
                $report['lv_Hypertrophy_input_value'] = $ivs;
                if ($isMale) {
                    if ($ivs < 11) {
                        $auto_hypertrophy = 'No LVH';
                    } elseif ($ivs < 14) {
                        $auto_hypertrophy = 'Mild LVH';
                    } elseif ($ivs <= 16) {
                        $auto_hypertrophy = 'Moderate LVH';
                    } else {
                        $auto_hypertrophy = 'Severe LVH';
                    }
                } else {
                    if ($ivs < 10) {
                        $auto_hypertrophy = 'No LVH';
                    } elseif ($ivs < 13) {
                        $auto_hypertrophy = 'Mild LVH';
                    } elseif ($ivs <= 15) {
                        $auto_hypertrophy = 'Moderate LVH';
                    } else {
                        $auto_hypertrophy = 'Severe LVH';
                    }
                }
            }

            if ($auto_hypertrophy !== null) {
                $report['lv_Hypertrophy'] = $auto_hypertrophy;
            } elseif ($isOriginalAuto) {
                $report['lv_Hypertrophy'] = '';
            }
        }

        // Auto-calculate cp_SPAP if not entered by user (i.e. empty, or "Auto")
        $trgVal = isset($report['tv_TRG']) && is_numeric($report['tv_TRG']) ? (float) $report['tv_TRG'] : 0;
        $ivcSizeVal = isset($report['cp_ivc_size']) && is_numeric($report['cp_ivc_size']) ? (float) $report['cp_ivc_size'] : null;
        $respVarVal = trim(strtolower($report['cp_Respiratory_variation'] ?? ''));

        $hasSpapInputs = ($trgVal > 0 && $ivcSizeVal !== null && $respVarVal !== '' && $respVarVal !== 'none');
        if ($hasSpapInputs) {
            $isCollapseOver50 = (strpos($respVarVal, '>50%') !== false || strpos($respVarVal, '> 50%') !== false);
            $rap = 0;
            if ($ivcSizeVal <= 21) {
                $rap = $isCollapseOver50 ? 3 : 8;
            } else {
                $rap = $isCollapseOver50 ? 8 : 15;
            }
            $calculatedSpap = round($trgVal + $rap, 1);
            if (empty($report['cp_SPAP']) || strcasecmp((string) $report['cp_SPAP'], 'Auto') === 0) {
                $report['cp_SPAP'] = $calculatedSpap;
                $report['cp_SPAP_was_auto'] = true;
            } elseif (is_numeric($report['cp_SPAP']) && abs((float) $report['cp_SPAP'] - $calculatedSpap) < 0.01) {
                $report['cp_SPAP_was_auto'] = true;
            }
            $report['cp_SPAP_input_used'] = 'TRG + RAP';
            $report['cp_SPAP_input_value'] = $trgVal.' + '.$rap;
            $report['cp_SPAP_rap'] = $rap;
        }

        // Auto-calculate cp_quantity based on SPAP range: <35 normal, 35-44 Mild PH, 45-59 Moderate PH, >=60 severe
        if (isset($report['cp_SPAP']) && is_numeric($report['cp_SPAP'])) {
            $spapVal = (float) $report['cp_SPAP'];
            $expectedQty = '';
            if ($spapVal < 35) {
                $expectedQty = 'Normal PAP';
            } elseif ($spapVal <= 44) {
                $expectedQty = 'Mild PH';
            } elseif ($spapVal <= 59) {
                $expectedQty = 'Moderate PH';
            } else {
                $expectedQty = 'Severe PH';
            }
            if (empty($report['cp_quantity']) || strcasecmp((string) $report['cp_quantity'], 'Auto') === 0) {
                $report['cp_quantity'] = $expectedQty;
                $report['cp_quantity_was_auto'] = true;
            } elseif (strcasecmp(trim($report['cp_quantity']), $expectedQty) === 0) {
                $report['cp_quantity_was_auto'] = true;
            }
            $report['cp_quantity_input_used'] = 'SPAP';
            $report['cp_quantity_input_value'] = $spapVal;
        } elseif (empty($report['cp_quantity']) || strcasecmp((string) $report['cp_quantity'], 'Auto') === 0) {
            $report['cp_quantity'] = '';
        }

        // Auto-calculate lv_Diastolic if not entered by user (i.e. empty, or "Auto")
        if (empty($report['lv_Diastolic']) || strcasecmp($report['lv_Diastolic'], 'Auto') === 0) {
            $isOriginalAuto = (strcasecmp($report['lv_Diastolic'] ?? '', 'Auto') === 0);

            $septale = isset($report['lv_septale']) && is_numeric($report['lv_septale']) ? (float) $report['lv_septale'] : 0;
            $lateral = isset($report['lv_lateral']) && is_numeric($report['lv_lateral']) ? (float) $report['lv_lateral'] : 0;
            $E = isset($report['mv_PE_Velocity']) && is_numeric($report['mv_PE_Velocity']) ? (float) $report['mv_PE_Velocity'] : 0;
            $A = isset($report['mv_PA_Velocity']) && is_numeric($report['mv_PA_Velocity']) ? (float) $report['mv_PA_Velocity'] : 0;
            $trg = isset($report['tv_TRG']) && is_numeric($report['tv_TRG']) ? (float) $report['tv_TRG'] : 0;
            $pasp = isset($report['cp_SPAP']) && is_numeric($report['cp_SPAP']) ? (float) $report['cp_SPAP'] : 0;

            $has_any_diastolic_inputs = ($septale > 0 || $lateral > 0 || $E > 0 || $A > 0 || $trg > 0 || $pasp > 0);

            if ($has_any_diastolic_inputs) {
                // 1. Reduced e' velocity: septal <= 6 or lateral <= 7 or average <= 6.5
                $has_reduced_e = false;
                if ($septale > 0 && $septale <= 6) {
                    $has_reduced_e = true;
                }
                if ($lateral > 0 && $lateral <= 7) {
                    $has_reduced_e = true;
                }
                if ($septale > 0 && $lateral > 0 && (($septale + $lateral) / 2) <= 6.5) {
                    $has_reduced_e = true;
                }

                // 2. Increased E/e': septal >= 15 or lateral >= 13 or average >= 14
                $has_increased_Ee = false;
                if ($E > 0) {
                    if ($septale > 0 && ($E * 100 / $septale) >= 15) {
                        $has_increased_Ee = true;
                    }
                    if ($lateral > 0 && ($E * 100 / $lateral) >= 13) {
                        $has_increased_Ee = true;
                    }
                    if ($septale > 0 && $lateral > 0 && ($E * 100 / (($septale + $lateral) / 2)) >= 14) {
                        $has_increased_Ee = true;
                    }
                }

                // 3. Increased TR velocity >= 2.8 or PASP >= 35
                $tr_velocity = $trg > 0 ? sqrt($trg / 4) : 0;
                $has_increased_TR_PASP = ($tr_velocity >= 2.8 || $pasp >= 35);

                // Calculate E/A ratio
                $ea_ratio = ($E > 0 && $A > 0) ? ($E / $A) : null;

                // Count abnormal variables
                $abnormal_count = 0;
                if ($has_reduced_e) {
                    $abnormal_count++;
                }
                if ($has_increased_Ee) {
                    $abnormal_count++;
                }
                if ($has_increased_TR_PASP) {
                    $abnormal_count++;
                }

                $auto_diastolic = 'Normal diastolic function';
                $highlightGroup = 'All Normal Group';

                if ($has_reduced_e && ! $has_increased_Ee && ! $has_increased_TR_PASP) {
                    // Reduced e' only
                    if ($ea_ratio !== null && $ea_ratio > 0.8) {
                        $auto_diastolic = 'Indeterminate diastolic function';
                        $highlightGroup = "Reduced e' only Group";
                    } else {
                        $auto_diastolic = 'Grade I diastolic dysfunction';
                        $highlightGroup = "Reduced e' only Group";
                    }
                } elseif ($has_reduced_e && $has_increased_Ee && $has_increased_TR_PASP) {
                    // 3 of the above (Increased LAP)
                    if ($ea_ratio !== null && $ea_ratio >= 2.0) {
                        $auto_diastolic = 'Grade III diastolic dysfunction';
                        $highlightGroup = '3 Abnormal Group';
                    } else {
                        $auto_diastolic = 'Grade II diastolic dysfunction';
                        $highlightGroup = '3 Abnormal Group';
                    }
                } elseif ($abnormal_count > 0) {
                    // 1 or 2 abnormal variables (Intermediate cases)
                    $auto_diastolic = 'Indeterminate diastolic function';
                    $highlightGroup = '1-2 Abnormal Group';
                }

                $report['lv_Diastolic'] = $auto_diastolic;
                $report['lv_Diastolic_was_auto'] = true;
                $report['lv_Diastolic_input_used'] = $highlightGroup;

                // Build parenthesized values string
                $diastolic_inputs = [];
                if ($septale > 0 && $lateral > 0) {
                    $diastolic_inputs[] = "e' (septal=".$septale.', lateral='.$lateral.') cm/s';
                } elseif ($septale > 0) {
                    $diastolic_inputs[] = "septal e' = ".$septale.' cm/s';
                } elseif ($lateral > 0) {
                    $diastolic_inputs[] = "lateral e' = ".$lateral.' cm/s';
                }
                if ($E > 0) {
                    if ($septale > 0 && $lateral > 0) {
                        $avg_e = ($septale + $lateral) / 2;
                        $avg_Ee = round(($E * 100) / $avg_e, 1);
                        $diastolic_inputs[] = "average E/e' = ".$avg_Ee;
                    } elseif ($septale > 0) {
                        $septal_Ee = round(($E * 100) / $septale, 1);
                        $diastolic_inputs[] = "septal E/e' = ".$septal_Ee;
                    } elseif ($lateral > 0) {
                        $lateral_Ee = round(($E * 100) / $lateral, 1);
                        $diastolic_inputs[] = "lateral E/e' = ".$lateral_Ee;
                    }
                }
                if ($ea_ratio !== null) {
                    $diastolic_inputs[] = 'E/A = '.round($ea_ratio, 2);
                }
                if ($pasp > 0) {
                    $diastolic_inputs[] = 'PASP = '.$pasp.' mmHg';
                } elseif ($tr_velocity > 0) {
                    $diastolic_inputs[] = 'TR Vel = '.round($tr_velocity, 2).' m/s';
                }

                $report['lv_Diastolic_input_value'] = implode(', ', $diastolic_inputs);

            } else {
                if ($isOriginalAuto) {
                    $report['lv_Diastolic'] = 'Normal diastolic function';
                }
            }
        }

        // Auto-calculate lv_clot if not entered by user (i.e. empty, or "Auto")
        if (empty($report['lv_clot']) || strcasecmp($report['lv_clot'], 'Auto') === 0) {
            $report['lv_clot'] = 'No LV clot';
        }

        // Auto-calculate rv_size if not entered by user (i.e. empty, or "Auto")
        if (empty($report['rv_size']) || strcasecmp($report['rv_size'], 'Auto') === 0) {
            $isOriginalAuto = (strcasecmp($report['rv_size'] ?? '', 'Auto') === 0);
            $rv_basal = isset($report['rv_RV_basal_diameter']) && is_numeric($report['rv_RV_basal_diameter']) ? (float) $report['rv_RV_basal_diameter'] : 0;
            $rv_mid = isset($report['rv_RV_mid_diameter']) && is_numeric($report['rv_RV_mid_diameter']) ? (float) $report['rv_RV_mid_diameter'] : 0;

            $auto_rv_size = null;
            if ($rv_basal > 0) {
                $report['rv_size_was_auto'] = true;
                $report['rv_size_input_used'] = 'RV Basal';
                $report['rv_size_input_value'] = $rv_basal;
                if ($rv_basal <= 40) {
                    $auto_rv_size = 'Normal RV size';
                } elseif ($rv_basal <= 44) {
                    $auto_rv_size = 'Mild RV enlargement';
                } elseif ($rv_basal <= 49) {
                    $auto_rv_size = 'Moderate RV enlargement';
                } else {
                    $auto_rv_size = 'Severe RV enlargement';
                }
            } elseif ($rv_mid > 0) {
                $report['rv_size_was_auto'] = true;
                $report['rv_size_input_used'] = 'RV Mid';
                $report['rv_size_input_value'] = $rv_mid;
                if ($rv_mid <= 35) {
                    $auto_rv_size = 'Normal RV size';
                } elseif ($rv_mid <= 38) {
                    $auto_rv_size = 'Mild RV enlargement';
                } elseif ($rv_mid <= 43) {
                    $auto_rv_size = 'Moderate RV enlargement';
                } else {
                    $auto_rv_size = 'Severe RV enlargement';
                }
            }

            if ($auto_rv_size !== null) {
                $report['rv_size'] = $auto_rv_size;
            } elseif ($isOriginalAuto) {
                $report['rv_size'] = '';
            }
        }

        // Auto-calculate rv_Systolic if not entered by user (i.e. empty, or "Auto")
        if (empty($report['rv_Systolic']) || strcasecmp($report['rv_Systolic'], 'Auto') === 0) {
            $isOriginalAuto = (strcasecmp($report['rv_Systolic'] ?? '', 'Auto') === 0);
            $tapse = isset($report['rv_TAPSE']) && is_numeric($report['rv_TAPSE']) ? (float) $report['rv_TAPSE'] : 0;

            $auto_rv_systolic = null;
            if ($tapse > 0) {
                $report['rv_Systolic_was_auto'] = true;
                $report['rv_Systolic_input_used'] = 'TAPSE';
                $report['rv_Systolic_input_value'] = $tapse;
                if ($tapse > 17) {
                    $auto_rv_systolic = 'With normal systolic function';
                } elseif ($tapse >= 14) {
                    $auto_rv_systolic = 'With mild systolic dysfunction';
                } elseif ($tapse >= 10) {
                    $auto_rv_systolic = 'With moderate systolic dysfunction';
                } else {
                    $auto_rv_systolic = 'With severe systolic dysfunction';
                }
            }

            if ($auto_rv_systolic !== null) {
                $report['rv_Systolic'] = $auto_rv_systolic;
            } elseif ($isOriginalAuto) {
                $report['rv_Systolic'] = '';
            }
        }

        // Auto-calculate rv_Hypertrophy if not entered by user (i.e. empty, or "Auto")
        if (empty($report['rv_Hypertrophy']) || strcasecmp($report['rv_Hypertrophy'], 'Auto') === 0) {
            $report['rv_Hypertrophy'] = 'No RVH';
        }

        // Auto-calculate la_size if not entered by user (i.e. empty, or "Auto")
        if (empty($report['la_size']) || strcasecmp($report['la_size'], 'Auto') === 0) {
            $isOriginalAuto = (strcasecmp($report['la_size'] ?? '', 'Auto') === 0);
            $la_volume = isset($report['la_volume']) && is_numeric($report['la_volume']) ? (float) $report['la_volume'] : 0;
            $la_area = isset($report['la_area']) && is_numeric($report['la_area']) ? (float) $report['la_area'] : 0;
            $la_diameter = isset($report['la_diameter']) && is_numeric($report['la_diameter']) ? (float) $report['la_diameter'] : 0;
            $gender = isset($report['gender']) ? trim($report['gender']) : '';
            $isMale = (strcasecmp($gender, 'Male') === 0 || $gender === '1');
            $isFemale = (strcasecmp($gender, 'Female') === 0 || $gender === '2');

            $auto_la_size = null;
            if ($la_volume > 0 && $bsa > 0) {
                $report['la_size_was_auto'] = true;
                $report['la_size_input_used'] = 'LAVI';
                $lavi = $la_volume / $bsa;
                $report['la_size_input_value'] = round($lavi, 1);
                if ($lavi <= 34) {
                    $auto_la_size = 'Normal LA size';
                } elseif ($lavi <= 41) {
                    $auto_la_size = 'Mild LA enlargement';
                } elseif ($lavi <= 48) {
                    $auto_la_size = 'Moderate LA enlargement';
                } else {
                    $auto_la_size = 'Severe LA enlargement';
                }
            } elseif ($la_area > 0) {
                $report['la_size_was_auto'] = true;
                $report['la_size_input_used'] = 'LA Area';
                $report['la_size_input_value'] = $la_area;
                if ($la_area <= 20) {
                    $auto_la_size = 'Normal LA size';
                } elseif ($la_area <= 30) {
                    $auto_la_size = 'Mild LA enlargement';
                } elseif ($la_area <= 41) {
                    $auto_la_size = 'Moderate LA enlargement';
                } else {
                    $auto_la_size = 'Severe LA enlargement';
                }
            } elseif ($la_diameter > 0 && ($isMale || $isFemale)) {
                if ($bsa > 0) {
                    $ladi = $la_diameter / $bsa;
                    $report['la_size_was_auto'] = true;
                    $report['la_size_input_used'] = 'LADI';
                    $report['la_size_input_value'] = round($ladi, 1);
                    if ($ladi <= 23) {
                        $auto_la_size = 'Normal LA size';
                    } else {
                        $auto_la_size = 'There is LA enlargement';
                    }
                } else {
                    $report['la_size_was_auto'] = true;
                    $report['la_size_input_used'] = 'LA Diam';
                    $report['la_size_input_value'] = $la_diameter;
                    if ($isMale) {
                        if ($la_diameter <= 40) {
                            $auto_la_size = 'Normal LA size';
                        } else {
                            $auto_la_size = 'There is LA enlargement';
                        }
                    } else {
                        if ($la_diameter <= 38) {
                            $auto_la_size = 'Normal LA size';
                        } else {
                            $auto_la_size = 'There is LA enlargement';
                        }
                    }
                }
            }

            if ($auto_la_size !== null) {
                $report['la_size'] = $auto_la_size;
            } elseif ($isOriginalAuto) {
                $report['la_size'] = '';
            }
        }

        // Auto-calculate ra_size if not entered by user (i.e. empty, or "Auto")
        if (empty($report['ra_size']) || strcasecmp($report['ra_size'], 'Auto') === 0) {
            $isOriginalAuto = (strcasecmp($report['ra_size'] ?? '', 'Auto') === 0);
            $ra_area = isset($report['ra_area']) && is_numeric($report['ra_area']) ? (float) $report['ra_area'] : 0;

            $auto_ra_size = null;
            if ($ra_area > 0) {
                $report['ra_size_was_auto'] = true;
                $report['ra_size_input_used'] = 'RA Area';
                $report['ra_size_input_value'] = $ra_area;
                if ($ra_area < 18) {
                    $auto_ra_size = 'Normal RA size';
                } else {
                    $auto_ra_size = 'RA enlargement';
                }
            }

            if ($auto_ra_size !== null) {
                $report['ra_size'] = $auto_ra_size;
            } elseif ($isOriginalAuto) {
                $report['ra_size'] = '';
            }
        }

        // Auto-calculate av_aorta if Auto or empty, or evaluate dilated segments if Dilated aortic size
        $gender = isset($report['gender']) ? trim($report['gender']) : '';
        $isMale = (strcasecmp($gender, 'Male') === 0 || $gender === '1');
        $isFemale = (strcasecmp($gender, 'Female') === 0 || $gender === '2');

        $annulus = isset($report['av_annulus']) && is_numeric($report['av_annulus']) ? (float) $report['av_annulus'] : (isset($report['AV_annulus']) && is_numeric($report['AV_annulus']) ? (float) $report['AV_annulus'] : 0);
        $sinusValsalva = isset($report['av_sinus_valsalva']) && is_numeric($report['av_sinus_valsalva']) ? (float) $report['av_sinus_valsalva'] : (isset($report['Sinus_valsalva']) && is_numeric($report['Sinus_valsalva']) ? (float) $report['Sinus_valsalva'] : 0);
        $stJunction = isset($report['av_st_junction']) && is_numeric($report['av_st_junction']) ? (float) $report['av_st_junction'] : (isset($report['ST_junction']) && is_numeric($report['ST_junction']) ? (float) $report['ST_junction'] : 0);
        $proxAscAorta = isset($report['av_proximal_ascending_aorta']) && is_numeric($report['av_proximal_ascending_aorta']) ? (float) $report['av_proximal_ascending_aorta'] : (isset($report['ascending_aorta']) && is_numeric($report['ascending_aorta']) ? (float) $report['ascending_aorta'] : 0);

        $dilatedAortaSegments = [];
        $anyAortaMeasured = ($annulus > 0 || $sinusValsalva > 0 || $stJunction > 0 || $proxAscAorta > 0);

        // 1. Annulus: abs{male:>29, female:>25}, indexed{male:14, female:14}
        if ($annulus > 0) {
            $absLimit = $isFemale ? 25 : 29;
            $idxLimit = 14;
            $idxVal = ($bsa > 0) ? ($annulus / $bsa) : 0;
            if ($annulus > $absLimit || ($bsa > 0 && $idxVal > $idxLimit)) {
                $dilatedAortaSegments[] = 'annulus';
            }
        }

        // 2. Sinuses of Valsalva: abs{male:>37, female:>33}, indexed{male:19, female:20}
        if ($sinusValsalva > 0) {
            $absLimit = $isFemale ? 33 : 37;
            $idxLimit = $isFemale ? 20 : 19;
            $idxVal = ($bsa > 0) ? ($sinusValsalva / $bsa) : 0;
            if ($sinusValsalva > $absLimit || ($bsa > 0 && $idxVal > $idxLimit)) {
                $dilatedAortaSegments[] = 'sinuses of valsalva';
            }
        }

        // 3. Sinotubular Junction: abs{male:>32, female:>29}, indexed{male:17, female:17}
        if ($stJunction > 0) {
            $absLimit = $isFemale ? 29 : 32;
            $idxLimit = 17;
            $idxVal = ($bsa > 0) ? ($stJunction / $bsa) : 0;
            if ($stJunction > $absLimit || ($bsa > 0 && $idxVal > $idxLimit)) {
                $dilatedAortaSegments[] = 'sinotubular junction';
            }
        }

        // 4. Proximal Ascending Aorta: abs{male:>34, female:>31}, indexed{male:17, female:19}
        if ($proxAscAorta > 0) {
            $absLimit = $isFemale ? 31 : 34;
            $idxLimit = $isFemale ? 19 : 17;
            $idxVal = ($bsa > 0) ? ($proxAscAorta / $bsa) : 0;
            if ($proxAscAorta > $absLimit || ($bsa > 0 && $idxVal > $idxLimit)) {
                $dilatedAortaSegments[] = 'proximal ascending aorta';
            }
        }

        $report['av_aorta_dilated_segments'] = $dilatedAortaSegments;

        $rawAortaStatus = trim($report['av_aorta'] ?? '');
        $isAortaAuto = (empty($rawAortaStatus) || strcasecmp($rawAortaStatus, 'Auto') === 0);

        if ($isAortaAuto) {
            if (! empty($dilatedAortaSegments)) {
                $report['av_aorta'] = 'dilated aortic size ('.implode(', ', $dilatedAortaSegments).')';
                $report['av_aorta_was_auto'] = true;
            } elseif ($anyAortaMeasured) {
                $report['av_aorta'] = 'normal aortic size';
                $report['av_aorta_was_auto'] = true;
            } else {
                $report['av_aorta'] = '';
            }
        } elseif (strcasecmp($rawAortaStatus, 'Dilated aortic size') === 0) {
            if (! empty($dilatedAortaSegments)) {
                $report['av_aorta'] = 'dilated aortic size ('.implode(', ', $dilatedAortaSegments).')';
            } else {
                $report['av_aorta'] = 'dilated aortic size';
            }
        } elseif (strcasecmp($rawAortaStatus, 'Normal aortic size') === 0) {
            $report['av_aorta'] = 'normal aortic size';
        } elseif (strcasecmp($rawAortaStatus, 'None') === 0) {
            $report['av_aorta'] = 'None';
        }

        return $report;
    }
}
