<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/data/v1alpha/data.proto
namespace GPBMetadata\Google\Analytics\Data\V1Alpha;

class Data
{
    public static $is_initialized = \false;
    public static function initOnce()
    {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();
        if (static::$is_initialized == \true) {
            return;
        }
        $pool->internalAddGeneratedFile('
�-
(google/analytics/data/v1alpha/data.protogoogle.analytics.data.v1alpha"?
	DateRange

start_date (	
end_date (	
name (	"
Entity
property_id (	"k
	Dimension
name (	P
dimension_expression (22.google.analytics.data.v1alpha.DimensionExpression"�
DimensionExpressionW

lower_case (2A.google.analytics.data.v1alpha.DimensionExpression.CaseExpressionH W

upper_case (2A.google.analytics.data.v1alpha.DimensionExpression.CaseExpressionH _
concatenate (2H.google.analytics.data.v1alpha.DimensionExpression.ConcatenateExpressionH (
CaseExpression
dimension_name (	C
ConcatenateExpression
dimension_names (	
	delimiter (	B
one_expression"=
Metric
name (	

expression (	
	invisible ("�
FilterExpressionH
	and_group (23.google.analytics.data.v1alpha.FilterExpressionListH G
or_group (23.google.analytics.data.v1alpha.FilterExpressionListH I
not_expression (2/.google.analytics.data.v1alpha.FilterExpressionH 7
filter (2%.google.analytics.data.v1alpha.FilterH B
expr"\\
FilterExpressionListD
expressions (2/.google.analytics.data.v1alpha.FilterExpression"�
Filter

field_name (	
null_filter (H K
string_filter (22.google.analytics.data.v1alpha.Filter.StringFilterH L
in_list_filter (22.google.analytics.data.v1alpha.Filter.InListFilterH M
numeric_filter (23.google.analytics.data.v1alpha.Filter.NumericFilterH M
between_filter (23.google.analytics.data.v1alpha.Filter.BetweenFilterH �
StringFilterP

match_type (2<.google.analytics.data.v1alpha.Filter.StringFilter.MatchType
value (	
case_sensitive ("�
	MatchType
MATCH_TYPE_UNSPECIFIED 	
EXACT
BEGINS_WITH
	ENDS_WITH
CONTAINS
FULL_REGEXP
PARTIAL_REGEXP6
InListFilter
values (	
case_sensitive (�
NumericFilterP
	operation (2=.google.analytics.data.v1alpha.Filter.NumericFilter.Operation:
value (2+.google.analytics.data.v1alpha.NumericValue"�
	Operation
OPERATION_UNSPECIFIED 	
EQUAL
	LESS_THAN
LESS_THAN_OR_EQUAL
GREATER_THAN
GREATER_THAN_OR_EQUAL�
BetweenFilter?

from_value (2+.google.analytics.data.v1alpha.NumericValue=
to_value (2+.google.analytics.data.v1alpha.NumericValueB

one_filter"�
OrderByF
metric (24.google.analytics.data.v1alpha.OrderBy.MetricOrderByH L
	dimension (27.google.analytics.data.v1alpha.OrderBy.DimensionOrderByH D
pivot (23.google.analytics.data.v1alpha.OrderBy.PivotOrderByH 
desc ($
MetricOrderBy
metric_name (	�
DimensionOrderBy
dimension_name (	U

order_type (2A.google.analytics.data.v1alpha.OrderBy.DimensionOrderBy.OrderType"i
	OrderType
ORDER_TYPE_UNSPECIFIED 
ALPHANUMERIC!
CASE_INSENSITIVE_ALPHANUMERIC
NUMERIC�
PivotOrderBy
metric_name (	\\
pivot_selections (2B.google.analytics.data.v1alpha.OrderBy.PivotOrderBy.PivotSelectionA
PivotSelection
dimension_name (	
dimension_value (	B
one_order_by"�
Pivot
field_names (	9
	order_bys (2&.google.analytics.data.v1alpha.OrderBy
offset (
limit (M
metric_aggregations (20.google.analytics.data.v1alpha.MetricAggregation"�

CohortSpec6
cohorts (2%.google.analytics.data.v1alpha.CohortB
cohorts_range (2+.google.analytics.data.v1alpha.CohortsRangeS
cohort_report_settings (23.google.analytics.data.v1alpha.CohortReportSettings"g
Cohort
name (	
	dimension (	<

date_range (2(.google.analytics.data.v1alpha.DateRange"�
CohortsRangeL
granularity (27.google.analytics.data.v1alpha.CohortsRange.Granularity
start_offset (

end_offset ("N
Granularity
GRANULARITY_UNSPECIFIED 	
DAILY

WEEKLY
MONTHLY"*
CohortReportSettings

accumulate ("4
ResponseMetaData 
data_loss_from_other_row ("
DimensionHeader
name (	"U
MetricHeader
name (	7
type (2).google.analytics.data.v1alpha.MetricType"v
PivotHeaderT
pivot_dimension_headers (23.google.analytics.data.v1alpha.PivotDimensionHeader
	row_count ("_
PivotDimensionHeaderG
dimension_values (2-.google.analytics.data.v1alpha.DimensionValue"�
RowG
dimension_values (2-.google.analytics.data.v1alpha.DimensionValueA
metric_values (2*.google.analytics.data.v1alpha.MetricValue".
DimensionValue
value (	H B
	one_value"+
MetricValue
value (	H B
	one_value"J
NumericValue
int64_value (H 
double_value (H B
	one_value"�
PropertyQuotaB
tokens_per_day (2*.google.analytics.data.v1alpha.QuotaStatusC
tokens_per_hour (2*.google.analytics.data.v1alpha.QuotaStatusG
concurrent_requests (2*.google.analytics.data.v1alpha.QuotaStatusV
"server_errors_per_project_per_hour (2*.google.analytics.data.v1alpha.QuotaStatus"2
QuotaStatus
consumed (
	remaining ("�
DimensionMetadata
api_name (	
ui_name (	
description (	
deprecated_api_names (	
custom_definition ("�
MetricMetadata
api_name (	
ui_name (	
description (	
deprecated_api_names (	7
type (2).google.analytics.data.v1alpha.MetricType

expression (	
custom_definition (*g
MetricAggregation"
METRIC_AGGREGATION_UNSPECIFIED 	
TOTAL
MINIMUM
MAXIMUM	
COUNT*�

MetricType
METRIC_TYPE_UNSPECIFIED 
TYPE_INTEGER

TYPE_FLOAT
TYPE_SECONDS
TYPE_MILLISECONDS
TYPE_MINUTES

TYPE_HOURS
TYPE_STANDARD
TYPE_CURRENCY	
	TYPE_FEET


TYPE_MILES
TYPE_METERS
TYPE_KILOMETERSB{
!com.google.analytics.data.v1alphaBReportingApiProtoPZAgoogle.golang.org/genproto/googleapis/analytics/data/v1alpha;databproto3', \true);
        static::$is_initialized = \true;
    }
}
