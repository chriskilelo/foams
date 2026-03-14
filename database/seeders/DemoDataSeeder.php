<?php

namespace Database\Seeders;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Enums\IssueSeverity;
use App\Enums\IssueStatus;
use App\Enums\ReporterCategory;
use App\Enums\ResolutionType;
use App\Models\Asset;
use App\Models\County;
use App\Models\Issue;
use App\Models\IssueActivity;
use App\Models\Resolution;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    // ─── Real Kenya ICT asset definitions ─────────────────────────────────────

    /** @var list<array{code:string,name:string,type:AssetType,county_name:string,location:string,lat:float,lon:float,manufacturer:string,model:string,sn:string,status:AssetStatus,installed:string}> */
    private array $assetDefinitions = [
        // ── WiFi Hotspots (28) ────────────────────────────────────────────
        ['code' => 'WIFI-KE-001', 'name' => 'Nairobi CBD Kenyatta Avenue Hotspot', 'type' => AssetType::WifiHotspot, 'county_name' => 'Nairobi City', 'location' => 'Kenyatta Avenue, Nairobi CBD', 'lat' => -1.2864, 'lon' => 36.8172, 'manufacturer' => 'Ubiquiti', 'model' => 'UniFi AP AC Pro', 'sn' => 'UAP-PRO-20210315-001', 'status' => AssetStatus::Operational, 'installed' => '2021-03-15'],
        ['code' => 'WIFI-KE-002', 'name' => 'Tom Mboya Street Hotspot', 'type' => AssetType::WifiHotspot, 'county_name' => 'Nairobi City', 'location' => 'Tom Mboya Street, Nairobi', 'lat' => -1.2841, 'lon' => 36.8197, 'manufacturer' => 'Cisco', 'model' => 'Meraki MR33', 'sn' => 'MR33-20210610-002', 'status' => AssetStatus::Operational, 'installed' => '2021-06-10'],
        ['code' => 'WIFI-KE-003', 'name' => 'Kibera Community Centre WiFi', 'type' => AssetType::WifiHotspot, 'county_name' => 'Nairobi City', 'location' => 'Kibera Community Centre, Kibera', 'lat' => -1.3136, 'lon' => 36.7847, 'manufacturer' => 'Cambium', 'model' => 'ePMP 1000', 'sn' => 'EPMP-20200811-003', 'status' => AssetStatus::Degraded, 'installed' => '2020-08-11'],
        ['code' => 'WIFI-KE-004', 'name' => 'Westlands Shopping Centre Hotspot', 'type' => AssetType::WifiHotspot, 'county_name' => 'Nairobi City', 'location' => 'Westlands, Nairobi', 'lat' => -1.2631, 'lon' => 36.8031, 'manufacturer' => 'Ubiquiti', 'model' => 'UniFi AP AC Lite', 'sn' => 'UAP-LITE-20211201-004', 'status' => AssetStatus::Operational, 'installed' => '2021-12-01'],
        ['code' => 'WIFI-KE-005', 'name' => 'Eastleigh Bus Terminal Hotspot', 'type' => AssetType::WifiHotspot, 'county_name' => 'Nairobi City', 'location' => 'Eastleigh Section II, Nairobi', 'lat' => -1.2722, 'lon' => 36.8483, 'manufacturer' => 'TP-Link', 'model' => 'EAP245', 'sn' => 'EAP245-20220315-005', 'status' => AssetStatus::Down, 'installed' => '2022-03-15'],
        ['code' => 'WIFI-KE-006', 'name' => 'Mombasa Ferry Terminal Hotspot', 'type' => AssetType::WifiHotspot, 'county_name' => 'Mombasa', 'location' => 'Mombasa Ferry Terminal, Likoni', 'lat' => -4.0679, 'lon' => 39.6594, 'manufacturer' => 'Ruckus', 'model' => 'R510', 'sn' => 'R510-20200520-006', 'status' => AssetStatus::Operational, 'installed' => '2020-05-20'],
        ['code' => 'WIFI-KE-007', 'name' => 'Mombasa CBD Digo Road Hotspot', 'type' => AssetType::WifiHotspot, 'county_name' => 'Mombasa', 'location' => 'Digo Road, Mombasa CBD', 'lat' => -4.0568, 'lon' => 39.6650, 'manufacturer' => 'Cisco', 'model' => 'Meraki MR42', 'sn' => 'MR42-20210718-007', 'status' => AssetStatus::Operational, 'installed' => '2021-07-18'],
        ['code' => 'WIFI-KE-008', 'name' => 'Nyali Bridge Area Hotspot', 'type' => AssetType::WifiHotspot, 'county_name' => 'Mombasa', 'location' => 'Nyali Bridge, Mombasa', 'lat' => -4.0269, 'lon' => 39.7128, 'manufacturer' => 'Ubiquiti', 'model' => 'UniFi AP AC HD', 'sn' => 'UAP-HD-20220101-008', 'status' => AssetStatus::Maintenance, 'installed' => '2022-01-01'],
        ['code' => 'WIFI-KE-009', 'name' => 'Kisumu Central Market Hotspot', 'type' => AssetType::WifiHotspot, 'county_name' => 'Kisumu', 'location' => 'Kisumu Central Market', 'lat' => -0.0917, 'lon' => 34.7680, 'manufacturer' => 'Cambium', 'model' => 'ePMP 2000', 'sn' => 'EPMP2K-20210403-009', 'status' => AssetStatus::Operational, 'installed' => '2021-04-03'],
        ['code' => 'WIFI-KE-010', 'name' => 'Kisumu Port WiFi Zone', 'type' => AssetType::WifiHotspot, 'county_name' => 'Kisumu', 'location' => 'Kisumu Port, Lake Victoria Shore', 'lat' => -0.0997, 'lon' => 34.7602, 'manufacturer' => 'Ruckus', 'model' => 'R610', 'sn' => 'R610-20200901-010', 'status' => AssetStatus::Operational, 'installed' => '2020-09-01'],
        ['code' => 'WIFI-KE-011', 'name' => 'Nakuru Town Centre Hotspot', 'type' => AssetType::WifiHotspot, 'county_name' => 'Nakuru', 'location' => 'Nakuru Town Centre, Kenyatta Avenue', 'lat' => -0.3031, 'lon' => 36.0800, 'manufacturer' => 'TP-Link', 'model' => 'EAP660 HD', 'sn' => 'EAP660-20221005-011', 'status' => AssetStatus::Operational, 'installed' => '2022-10-05'],
        ['code' => 'WIFI-KE-012', 'name' => 'Nakuru Free Area Market Hotspot', 'type' => AssetType::WifiHotspot, 'county_name' => 'Nakuru', 'location' => 'Free Area Market, Nakuru', 'lat' => -0.2936, 'lon' => 36.0831, 'manufacturer' => 'Ubiquiti', 'model' => 'UniFi AP AC LR', 'sn' => 'UAP-LR-20210715-012', 'status' => AssetStatus::Degraded, 'installed' => '2021-07-15'],
        ['code' => 'WIFI-KE-013', 'name' => 'Eldoret Town Square Hotspot', 'type' => AssetType::WifiHotspot, 'county_name' => 'Uasin Gishu', 'location' => 'Eldoret Town Square, Uganda Road', 'lat' => 0.5143, 'lon' => 35.2698, 'manufacturer' => 'Cisco', 'model' => 'Meraki MR36', 'sn' => 'MR36-20230120-013', 'status' => AssetStatus::Operational, 'installed' => '2023-01-20'],
        ['code' => 'WIFI-KE-014', 'name' => 'Thika Municipal Market Hotspot', 'type' => AssetType::WifiHotspot, 'county_name' => 'Kiambu', 'location' => 'Thika Municipal Market', 'lat' => -1.0332, 'lon' => 37.0693, 'manufacturer' => 'Ubiquiti', 'model' => 'UniFi AP AC Pro', 'sn' => 'UAP-PRO-20211101-014', 'status' => AssetStatus::Operational, 'installed' => '2021-11-01'],
        ['code' => 'WIFI-KE-015', 'name' => 'Machakos Town Park Hotspot', 'type' => AssetType::WifiHotspot, 'county_name' => 'Machakos', 'location' => 'Machakos Town Park, Machakos', 'lat' => -1.5177, 'lon' => 37.2634, 'manufacturer' => 'Cambium', 'model' => 'cnPilot E410', 'sn' => 'E410-20220801-015', 'status' => AssetStatus::Down, 'installed' => '2022-08-01'],
        ['code' => 'WIFI-KE-016', 'name' => 'Meru Town Bus Park Hotspot', 'type' => AssetType::WifiHotspot, 'county_name' => 'Meru', 'location' => 'Meru Town Bus Park, Meru', 'lat' => 0.0464, 'lon' => 37.6492, 'manufacturer' => 'TP-Link', 'model' => 'EAP225-Outdoor', 'sn' => 'EAP225O-20230601-016', 'status' => AssetStatus::Operational, 'installed' => '2023-06-01'],
        ['code' => 'WIFI-KE-017', 'name' => 'Kakamega Central Market Hotspot', 'type' => AssetType::WifiHotspot, 'county_name' => 'Kakamega', 'location' => 'Kakamega Central Market', 'lat' => 0.2822, 'lon' => 34.7517, 'manufacturer' => 'Ubiquiti', 'model' => 'UniFi AP AC Mesh', 'sn' => 'UAP-MESH-20210901-017', 'status' => AssetStatus::Operational, 'installed' => '2021-09-01'],
        ['code' => 'WIFI-KE-018', 'name' => 'Kisii CBD Bus Terminal Hotspot', 'type' => AssetType::WifiHotspot, 'county_name' => 'Kisii', 'location' => 'Kisii CBD Bus Terminal', 'lat' => -0.6773, 'lon' => 34.7659, 'manufacturer' => 'Ruckus', 'model' => 'R350', 'sn' => 'R350-20220415-018', 'status' => AssetStatus::Operational, 'installed' => '2022-04-15'],
        ['code' => 'WIFI-KE-019', 'name' => 'Garissa Town Centre Hotspot', 'type' => AssetType::WifiHotspot, 'county_name' => 'Garissa', 'location' => 'Garissa Town Centre', 'lat' => -0.4521, 'lon' => 39.6404, 'manufacturer' => 'Cambium', 'model' => 'ePMP 3000', 'sn' => 'EPMP3K-20220201-019', 'status' => AssetStatus::Degraded, 'installed' => '2022-02-01'],
        ['code' => 'WIFI-KE-020', 'name' => 'Nyeri County Headquarters Hotspot', 'type' => AssetType::WifiHotspot, 'county_name' => 'Nyeri', 'location' => 'Nyeri County Headquarters', 'lat' => -0.4233, 'lon' => 36.9508, 'manufacturer' => 'Cisco', 'model' => 'Meraki MR46', 'sn' => 'MR46-20230315-020', 'status' => AssetStatus::Operational, 'installed' => '2023-03-15'],
        ['code' => 'WIFI-KE-021', 'name' => 'Kericho Town Square Hotspot', 'type' => AssetType::WifiHotspot, 'county_name' => 'Kericho', 'location' => 'Kericho Town Square', 'lat' => -0.3706, 'lon' => 35.2863, 'manufacturer' => 'Ubiquiti', 'model' => 'UniFi AP AC Pro', 'sn' => 'UAP-PRO-20211215-021', 'status' => AssetStatus::Operational, 'installed' => '2021-12-15'],
        ['code' => 'WIFI-KE-022', 'name' => 'Nanyuki Town Square Hotspot', 'type' => AssetType::WifiHotspot, 'county_name' => 'Laikipia', 'location' => 'Nanyuki Town Square, Laikipia', 'lat' => 0.0178, 'lon' => 37.0726, 'manufacturer' => 'TP-Link', 'model' => 'EAP670', 'sn' => 'EAP670-20230501-022', 'status' => AssetStatus::Operational, 'installed' => '2023-05-01'],
        ['code' => 'WIFI-KE-023', 'name' => 'Malindi Main Market Hotspot', 'type' => AssetType::WifiHotspot, 'county_name' => 'Kilifi', 'location' => 'Malindi Main Market, Kilifi County', 'lat' => -3.2138, 'lon' => 40.1169, 'manufacturer' => 'Ubiquiti', 'model' => 'UniFi AP AC Mesh Pro', 'sn' => 'UAP-MESH-PRO-20220901-023', 'status' => AssetStatus::Operational, 'installed' => '2022-09-01'],
        ['code' => 'WIFI-KE-024', 'name' => 'Lamu Old Town Waterfront Hotspot', 'type' => AssetType::WifiHotspot, 'county_name' => 'Lamu', 'location' => 'Lamu Old Town Jetty Area', 'lat' => -2.2686, 'lon' => 40.9020, 'manufacturer' => 'Cambium', 'model' => 'cnPilot E600', 'sn' => 'E600-20230101-024', 'status' => AssetStatus::Operational, 'installed' => '2023-01-01'],
        ['code' => 'WIFI-KE-025', 'name' => 'Lodwar Town Centre Hotspot', 'type' => AssetType::WifiHotspot, 'county_name' => 'Turkana', 'location' => 'Lodwar Town Centre, Turkana', 'lat' => 3.1188, 'lon' => 35.5975, 'manufacturer' => 'Cambium', 'model' => 'ePMP 1000', 'sn' => 'EPMP-20211005-025', 'status' => AssetStatus::Down, 'installed' => '2021-10-05'],
        ['code' => 'WIFI-KE-026', 'name' => 'Bungoma Central Bus Park Hotspot', 'type' => AssetType::WifiHotspot, 'county_name' => 'Bungoma', 'location' => 'Bungoma Central Bus Park', 'lat' => 0.5636, 'lon' => 34.5608, 'manufacturer' => 'Ruckus', 'model' => 'R550', 'sn' => 'R550-20220715-026', 'status' => AssetStatus::Operational, 'installed' => '2022-07-15'],
        ['code' => 'WIFI-KE-027', 'name' => 'Busia Border Market Hotspot', 'type' => AssetType::WifiHotspot, 'county_name' => 'Busia', 'location' => 'Busia Border Market', 'lat' => 0.4606, 'lon' => 34.1106, 'manufacturer' => 'Ubiquiti', 'model' => 'UniFi AP AC Lite', 'sn' => 'UAP-LITE-20221101-027', 'status' => AssetStatus::Operational, 'installed' => '2022-11-01'],
        ['code' => 'WIFI-KE-028', 'name' => 'Homa Bay Town Beach Hotspot', 'type' => AssetType::WifiHotspot, 'county_name' => 'Homa Bay', 'location' => 'Homa Bay Town Beach', 'lat' => -0.5275, 'lon' => 34.4578, 'manufacturer' => 'TP-Link', 'model' => 'EAP245', 'sn' => 'EAP245-20230201-028', 'status' => AssetStatus::Operational, 'installed' => '2023-02-01'],

        // ── NOFBI Nodes (20) ──────────────────────────────────────────────
        ['code' => 'NOFBI-KE-001', 'name' => 'Nairobi Regional NOFBI Hub Node', 'type' => AssetType::NofbiNode, 'county_name' => 'Nairobi City', 'location' => 'ICTA Nairobi Data Centre, Upper Hill', 'lat' => -1.2967, 'lon' => 36.8219, 'manufacturer' => 'Huawei', 'model' => 'OptiX OSN 9800', 'sn' => 'OSN9800-20190601-001', 'status' => AssetStatus::Operational, 'installed' => '2019-06-01'],
        ['code' => 'NOFBI-KE-002', 'name' => 'Mombasa Coast Regional NOFBI Node', 'type' => AssetType::NofbiNode, 'county_name' => 'Mombasa', 'location' => 'ICTA Coast Regional Office, Mombasa', 'lat' => -4.0559, 'lon' => 39.6581, 'manufacturer' => 'Nokia', 'model' => '7750 SR-7', 'sn' => 'SR7-20190801-002', 'status' => AssetStatus::Operational, 'installed' => '2019-08-01'],
        ['code' => 'NOFBI-KE-003', 'name' => 'Kisumu Nyanza NOFBI Node', 'type' => AssetType::NofbiNode, 'county_name' => 'Kisumu', 'location' => 'ICTA Nyanza Office, Kisumu', 'lat' => -0.0936, 'lon' => 34.7680, 'manufacturer' => 'ZTE', 'model' => 'ZXONE 9700', 'sn' => 'ZXONE-20200301-003', 'status' => AssetStatus::Operational, 'installed' => '2020-03-01'],
        ['code' => 'NOFBI-KE-004', 'name' => 'Nakuru Rift NOFBI Node', 'type' => AssetType::NofbiNode, 'county_name' => 'Nakuru', 'location' => 'ICTA Rift Valley Office, Nakuru', 'lat' => -0.2928, 'lon' => 36.0694, 'manufacturer' => 'Ciena', 'model' => '6500 Packet-Optical', 'sn' => 'CIENA6500-20200601-004', 'status' => AssetStatus::Operational, 'installed' => '2020-06-01'],
        ['code' => 'NOFBI-KE-005', 'name' => 'Eldoret North Rift NOFBI Node', 'type' => AssetType::NofbiNode, 'county_name' => 'Uasin Gishu', 'location' => 'ICTA North Rift Sub-Office, Eldoret', 'lat' => 0.5231, 'lon' => 35.2769, 'manufacturer' => 'Ericsson', 'model' => 'MINI-LINK 6352', 'sn' => 'ML6352-20200901-005', 'status' => AssetStatus::Degraded, 'installed' => '2020-09-01'],
        ['code' => 'NOFBI-KE-006', 'name' => 'Nyeri Central NOFBI Node', 'type' => AssetType::NofbiNode, 'county_name' => 'Nyeri', 'location' => 'Nyeri Data Exchange Point', 'lat' => -0.4166, 'lon' => 36.9478, 'manufacturer' => 'Huawei', 'model' => 'OptiX OSN 6800', 'sn' => 'OSN6800-20201101-006', 'status' => AssetStatus::Operational, 'installed' => '2020-11-01'],
        ['code' => 'NOFBI-KE-007', 'name' => 'Garissa North Eastern NOFBI Node', 'type' => AssetType::NofbiNode, 'county_name' => 'Garissa', 'location' => 'ICTA North Eastern Office, Garissa', 'lat' => -0.4521, 'lon' => 39.6404, 'manufacturer' => 'Nokia', 'model' => '1830 PSS', 'sn' => 'PSS-20210301-007', 'status' => AssetStatus::Down, 'installed' => '2021-03-01'],
        ['code' => 'NOFBI-KE-008', 'name' => 'Kakamega Western NOFBI Node', 'type' => AssetType::NofbiNode, 'county_name' => 'Kakamega', 'location' => 'ICTA Western Region Office, Kakamega', 'lat' => 0.2869, 'lon' => 34.7525, 'manufacturer' => 'ZTE', 'model' => 'ZXONE 19700', 'sn' => 'ZXONE197-20210601-008', 'status' => AssetStatus::Operational, 'installed' => '2021-06-01'],
        ['code' => 'NOFBI-KE-009', 'name' => 'Meru Eastern NOFBI Node', 'type' => AssetType::NofbiNode, 'county_name' => 'Meru', 'location' => 'Meru County IXP Hub', 'lat' => 0.0531, 'lon' => 37.6548, 'manufacturer' => 'Ciena', 'model' => '5170', 'sn' => 'CIENA5170-20210901-009', 'status' => AssetStatus::Operational, 'installed' => '2021-09-01'],
        ['code' => 'NOFBI-KE-010', 'name' => 'Embu Eastern NOFBI Node', 'type' => AssetType::NofbiNode, 'county_name' => 'Embu', 'location' => 'Embu County Government Complex', 'lat' => -0.5388, 'lon' => 37.4500, 'manufacturer' => 'Huawei', 'model' => 'OptiX OSN 3500', 'sn' => 'OSN3500-20211101-010', 'status' => AssetStatus::Operational, 'installed' => '2021-11-01'],
        ['code' => 'NOFBI-KE-011', 'name' => 'Machakos Eastern NOFBI Node', 'type' => AssetType::NofbiNode, 'county_name' => 'Machakos', 'location' => 'Machakos County Datacenter', 'lat' => -1.5202, 'lon' => 37.2632, 'manufacturer' => 'Ericsson', 'model' => 'MINI-LINK PT 2010', 'sn' => 'MLPT2010-20220101-011', 'status' => AssetStatus::Operational, 'installed' => '2022-01-01'],
        ['code' => 'NOFBI-KE-012', 'name' => 'Kitui Eastern NOFBI Node', 'type' => AssetType::NofbiNode, 'county_name' => 'Kitui', 'location' => 'Kitui County Government Offices', 'lat' => -1.3664, 'lon' => 38.0106, 'manufacturer' => 'Nokia', 'model' => '7705 SAR', 'sn' => 'SAR-20220301-012', 'status' => AssetStatus::Degraded, 'installed' => '2022-03-01'],
        ['code' => 'NOFBI-KE-013', 'name' => 'Kisii Nyanza NOFBI Node', 'type' => AssetType::NofbiNode, 'county_name' => 'Kisii', 'location' => 'Kisii County Exchange Point', 'lat' => -0.6817, 'lon' => 34.7714, 'manufacturer' => 'Huawei', 'model' => 'OptiX OSN 9800', 'sn' => 'OSN9800-20220501-013', 'status' => AssetStatus::Operational, 'installed' => '2022-05-01'],
        ['code' => 'NOFBI-KE-014', 'name' => 'Migori Nyanza NOFBI Node', 'type' => AssetType::NofbiNode, 'county_name' => 'Migori', 'location' => 'Migori County Network Hub', 'lat' => -1.0634, 'lon' => 34.4731, 'manufacturer' => 'ZTE', 'model' => 'ZXONE 9700', 'sn' => 'ZXONE-20220701-014', 'status' => AssetStatus::Operational, 'installed' => '2022-07-01'],
        ['code' => 'NOFBI-KE-015', 'name' => 'Wajir North Eastern NOFBI Node', 'type' => AssetType::NofbiNode, 'county_name' => 'Wajir', 'location' => 'Wajir County Offices', 'lat' => 1.7531, 'lon' => 40.0573, 'manufacturer' => 'Huawei', 'model' => 'OptiX OSN 1800', 'sn' => 'OSN1800-20220901-015', 'status' => AssetStatus::Down, 'installed' => '2022-09-01'],
        ['code' => 'NOFBI-KE-016', 'name' => 'Mandera North Eastern NOFBI Node', 'type' => AssetType::NofbiNode, 'county_name' => 'Mandera', 'location' => 'Mandera County Government Complex', 'lat' => 3.9366, 'lon' => 41.8669, 'manufacturer' => 'Ericsson', 'model' => 'MINI-LINK 6352', 'sn' => 'ML6352-20221101-016', 'status' => AssetStatus::Degraded, 'installed' => '2022-11-01'],
        ['code' => 'NOFBI-KE-017', 'name' => 'Kericho NOFBI Node', 'type' => AssetType::NofbiNode, 'county_name' => 'Kericho', 'location' => 'Kericho Tea Research Institute Area', 'lat' => -0.3706, 'lon' => 35.2863, 'manufacturer' => 'Nokia', 'model' => '7750 SR-1', 'sn' => 'SR1-20230101-017', 'status' => AssetStatus::Operational, 'installed' => '2023-01-01'],
        ['code' => 'NOFBI-KE-018', 'name' => 'Bomet NOFBI Node', 'type' => AssetType::NofbiNode, 'county_name' => 'Bomet', 'location' => 'Bomet County Offices', 'lat' => -0.7838, 'lon' => 35.3404, 'manufacturer' => 'Ciena', 'model' => '3930', 'sn' => 'CIENA3930-20230301-018', 'status' => AssetStatus::Operational, 'installed' => '2023-03-01'],
        ['code' => 'NOFBI-KE-019', 'name' => 'Siaya NOFBI Node', 'type' => AssetType::NofbiNode, 'county_name' => 'Siaya', 'location' => 'Siaya County Network Exchange', 'lat' => -0.0611, 'lon' => 34.2878, 'manufacturer' => 'Huawei', 'model' => 'OptiX OSN 6800', 'sn' => 'OSN6800-20230501-019', 'status' => AssetStatus::Operational, 'installed' => '2023-05-01'],
        ['code' => 'NOFBI-KE-020', 'name' => 'Marsabit NOFBI Node', 'type' => AssetType::NofbiNode, 'county_name' => 'Marsabit', 'location' => 'Marsabit County Complex', 'lat' => 2.3356, 'lon' => 37.9763, 'manufacturer' => 'ZTE', 'model' => 'ZXONE 5800', 'sn' => 'ZXONE58-20231001-020', 'status' => AssetStatus::Operational, 'installed' => '2023-10-01'],

        // ── OGN Equipment Sites (17) ──────────────────────────────────────
        ['code' => 'OGN-KE-001', 'name' => 'ICTA Headquarters OGN Core Switch', 'type' => AssetType::OgnEquipment, 'county_name' => 'Nairobi City', 'location' => 'ICTA HQ, Telposta Towers, Kenyatta Ave', 'lat' => -1.2855, 'lon' => 36.8210, 'manufacturer' => 'Cisco', 'model' => 'Catalyst 9500', 'sn' => 'C9500-20190401-001', 'status' => AssetStatus::Operational, 'installed' => '2019-04-01'],
        ['code' => 'OGN-KE-002', 'name' => 'State House OGN Firewall', 'type' => AssetType::OgnEquipment, 'county_name' => 'Nairobi City', 'location' => 'State House, Nairobi', 'lat' => -1.2711, 'lon' => 36.8055, 'manufacturer' => 'Fortinet', 'model' => 'FortiGate 1800F', 'sn' => 'FG1800F-20200201-002', 'status' => AssetStatus::Operational, 'installed' => '2020-02-01'],
        ['code' => 'OGN-KE-003', 'name' => 'Treasury OGN Distribution Switch', 'type' => AssetType::OgnEquipment, 'county_name' => 'Nairobi City', 'location' => 'National Treasury, Harambee Ave', 'lat' => -1.2819, 'lon' => 36.8231, 'manufacturer' => 'Juniper', 'model' => 'EX4300-48T', 'sn' => 'EX4300-20200601-003', 'status' => AssetStatus::Operational, 'installed' => '2020-06-01'],
        ['code' => 'OGN-KE-004', 'name' => 'Ministry of Health OGN Router', 'type' => AssetType::OgnEquipment, 'county_name' => 'Nairobi City', 'location' => 'Ministry of Health, Afya House', 'lat' => -1.2919, 'lon' => 36.8259, 'manufacturer' => 'Cisco', 'model' => 'ISR 4451-X', 'sn' => 'ISR4451-20201001-004', 'status' => AssetStatus::Operational, 'installed' => '2020-10-01'],
        ['code' => 'OGN-KE-005', 'name' => 'Coast Regional Commissioner OGN Node', 'type' => AssetType::OgnEquipment, 'county_name' => 'Mombasa', 'location' => 'Coast Regional Commissioner Office', 'lat' => -4.0617, 'lon' => 39.6631, 'manufacturer' => 'HP', 'model' => 'ProCurve 5406zl', 'sn' => 'HP5406-20210101-005', 'status' => AssetStatus::Operational, 'installed' => '2021-01-01'],
        ['code' => 'OGN-KE-006', 'name' => 'Kisumu Regional Office OGN Switch', 'type' => AssetType::OgnEquipment, 'county_name' => 'Kisumu', 'location' => 'Nyanza Regional Commissioner Office', 'lat' => -0.0936, 'lon' => 34.7653, 'manufacturer' => 'Aruba', 'model' => '2930F-48G', 'sn' => 'ARUBA2930-20210301-006', 'status' => AssetStatus::Operational, 'installed' => '2021-03-01'],
        ['code' => 'OGN-KE-007', 'name' => 'Nakuru County Government OGN Firewall', 'type' => AssetType::OgnEquipment, 'county_name' => 'Nakuru', 'location' => 'Nakuru County Government HQ', 'lat' => -0.3019, 'lon' => 36.0769, 'manufacturer' => 'Fortinet', 'model' => 'FortiGate 100F', 'sn' => 'FG100F-20210601-007', 'status' => AssetStatus::Degraded, 'installed' => '2021-06-01'],
        ['code' => 'OGN-KE-008', 'name' => 'Eldoret Regional Office OGN Switch', 'type' => AssetType::OgnEquipment, 'county_name' => 'Uasin Gishu', 'location' => 'North Rift Regional Office, Eldoret', 'lat' => 0.5162, 'lon' => 35.2712, 'manufacturer' => 'Cisco', 'model' => 'Catalyst 9300', 'sn' => 'C9300-20211001-008', 'status' => AssetStatus::Operational, 'installed' => '2021-10-01'],
        ['code' => 'OGN-KE-009', 'name' => 'Nyeri County OGN Core Router', 'type' => AssetType::OgnEquipment, 'county_name' => 'Nyeri', 'location' => 'Nyeri County Government HQ', 'lat' => -0.4233, 'lon' => 36.9508, 'manufacturer' => 'Juniper', 'model' => 'MX204', 'sn' => 'MX204-20211201-009', 'status' => AssetStatus::Operational, 'installed' => '2021-12-01'],
        ['code' => 'OGN-KE-010', 'name' => 'Meru County OGN Distribution Switch', 'type' => AssetType::OgnEquipment, 'county_name' => 'Meru', 'location' => 'Meru County Government Complex', 'lat' => 0.0519, 'lon' => 37.6500, 'manufacturer' => 'Aruba', 'model' => '3810M-24G', 'sn' => 'ARUBA3810-20220101-010', 'status' => AssetStatus::Operational, 'installed' => '2022-01-01'],
        ['code' => 'OGN-KE-011', 'name' => 'Garissa County OGN Firewall', 'type' => AssetType::OgnEquipment, 'county_name' => 'Garissa', 'location' => 'Garissa County Government Offices', 'lat' => -0.4603, 'lon' => 39.6461, 'manufacturer' => 'Fortinet', 'model' => 'FortiGate 60F', 'sn' => 'FG60F-20220301-011', 'status' => AssetStatus::Operational, 'installed' => '2022-03-01'],
        ['code' => 'OGN-KE-012', 'name' => 'Kakamega Western OGN Router', 'type' => AssetType::OgnEquipment, 'county_name' => 'Kakamega', 'location' => 'Western Regional Commissioner Office', 'lat' => 0.2848, 'lon' => 34.7531, 'manufacturer' => 'Cisco', 'model' => 'ISR 4331', 'sn' => 'ISR4331-20220501-012', 'status' => AssetStatus::Maintenance, 'installed' => '2022-05-01'],
        ['code' => 'OGN-KE-013', 'name' => 'Machakos County OGN Switch', 'type' => AssetType::OgnEquipment, 'county_name' => 'Machakos', 'location' => 'Machakos County Government HQ', 'lat' => -1.5177, 'lon' => 37.2648, 'manufacturer' => 'HP', 'model' => 'ProCurve 2920-48G', 'sn' => 'HP2920-20220701-013', 'status' => AssetStatus::Operational, 'installed' => '2022-07-01'],
        ['code' => 'OGN-KE-014', 'name' => 'Kisii County OGN Router', 'type' => AssetType::OgnEquipment, 'county_name' => 'Kisii', 'location' => 'Kisii County Government Complex', 'lat' => -0.6773, 'lon' => 34.7717, 'manufacturer' => 'Juniper', 'model' => 'SRX300', 'sn' => 'SRX300-20220901-014', 'status' => AssetStatus::Operational, 'installed' => '2022-09-01'],
        ['code' => 'OGN-KE-015', 'name' => 'Wajir County OGN Switch', 'type' => AssetType::OgnEquipment, 'county_name' => 'Wajir', 'location' => 'Wajir County Government Offices', 'lat' => 1.7519, 'lon' => 40.0581, 'manufacturer' => 'Aruba', 'model' => '2530-24G', 'sn' => 'ARUBA2530-20221101-015', 'status' => AssetStatus::Down, 'installed' => '2022-11-01'],
        ['code' => 'OGN-KE-016', 'name' => 'Siaya County OGN Firewall', 'type' => AssetType::OgnEquipment, 'county_name' => 'Siaya', 'location' => 'Siaya County Government HQ', 'lat' => -0.0611, 'lon' => 34.2878, 'manufacturer' => 'Fortinet', 'model' => 'FortiGate 40F', 'sn' => 'FG40F-20230101-016', 'status' => AssetStatus::Operational, 'installed' => '2023-01-01'],
        ['code' => 'OGN-KE-017', 'name' => 'Mandera County OGN Router', 'type' => AssetType::OgnEquipment, 'county_name' => 'Mandera', 'location' => 'Mandera County Government Complex', 'lat' => 3.9414, 'lon' => 41.8578, 'manufacturer' => 'Cisco', 'model' => 'ISR 4221', 'sn' => 'ISR4221-20231101-017', 'status' => AssetStatus::Operational, 'installed' => '2023-11-01'],
    ];

    // ─── Issue pool data (for realistic generation) ────────────────────────────

    /** @var list<array{type:string,severity_weight:array<string,int>,description_template:string}> */
    private array $issuePool = [
        [
            'type' => 'connectivity',
            'descriptions' => [
                'Users at %location% are unable to connect to the internet. The access point shows a solid red LED indicating no upstream connectivity. Approximately %count% users affected.',
                'Complete loss of connectivity reported at %location%. Field officer confirms the link to the upstream router is down. KPLC power fluctuation may be related.',
                'Intermittent connectivity drops at %location% — users experiencing disconnections every 10–15 minutes. Throughput has dropped from the usual 50 Mbps to under 2 Mbps.',
                'No internet access at %location% since %time%. Users unable to access government e-services portals. ISP upstream issue suspected.',
            ],
        ],
        [
            'type' => 'hardware_failure',
            'descriptions' => [
                'The %asset_type% at %location% is physically damaged. The casing is cracked and the unit is not powering on. Likely vandalism or impact from a vehicle.',
                'Power supply unit failure on %asset_type% at %location%. The unit is drawing erratic current readings. Replacement PSU has been ordered from the supplier.',
                'Antenna assembly on the %asset_type% at %location% has corroded due to coastal salt air exposure. Signal strength has dropped by 60% over the past three months.',
                'Motherboard failure suspected on %asset_type% unit at %location%. Unit reboots randomly every few hours. Diagnostics show memory errors in event logs.',
            ],
        ],
        [
            'type' => 'power_outage',
            'descriptions' => [
                'KPLC load-shedding has taken the %asset_type% at %location% offline. The UPS battery backup lasted approximately 2 hours before depletion. KPLC restoration ETA is unknown.',
                'Mains power failure at %location% since %time%. Generator fuel exhausted. Field officer has contacted KPLC for restoration timeline. UPS monitoring shows 0% charge.',
                'Lightning strike during last night\'s storm caused a power surge that destroyed the surge protector at %location%. The %asset_type% may have sustained damage.',
                'Scheduled KPLC maintenance at %location% sub-station resulted in extended outage beyond the communicated 4-hour window. Now 9 hours without mains power.',
            ],
        ],
        [
            'type' => 'vandalism',
            'descriptions' => [
                'The external antenna at %location% has been stolen. The mount bracket is bent and cables have been cut. This is the second incident in three months at this site.',
                'Graffiti and physical damage reported at the equipment cabinet housing the %asset_type% at %location%. Cabinet door has been forced open and one of the LAN cables has been pulled out.',
                'Solar panel powering the %asset_type% at %location% has been stolen overnight. The unit is now running on depleted battery backup. Police OB number: OB/123/2025.',
                'Network cable between the %asset_type% and the distribution point at %location% has been deliberately cut. Copper wire theft suspected.',
            ],
        ],
        [
            'type' => 'performance_degradation',
            'descriptions' => [
                'Users at %location% report severely degraded speeds. Speed tests show downstream of 0.8 Mbps against the expected 20 Mbps. Upstream link utilisation is at 98%.',
                'High latency (>500ms) observed on the %asset_type% at %location%. Packet loss rate is 15%. Issue has persisted for 3 days despite a remote reboot attempt.',
                'The %asset_type% at %location% is serving fewer concurrent users than capacity allows. Channel interference from nearby access points may be the cause.',
                'QoS misconfiguration suspected on %asset_type% at %location%. VoIP and video calls are being queued behind bulk downloads, causing severe jitter.',
            ],
        ],
        [
            'type' => 'fiber_cut',
            'descriptions' => [
                'NOFBI fiber cable serving %location% has been cut, reportedly during road construction by Kenya National Highways Authority (KeNHA). Approximately 2.3 km of cable affected.',
                'Fiber optic cable between %location% and the regional hub has been severed. A backhoe working on water supply infrastructure is the suspected cause. KURA notified.',
                'Multiple fiber cuts along the %location% route following the recent floods. Cable trenches were exposed by soil erosion. Approximately 800m of cable needs replacement.',
                'Underground fiber conduit collapsed at %location% due to subsidence. Full cable replacement required in the affected 400m stretch. Civil works tender to be floated.',
            ],
        ],
        [
            'type' => 'configuration_error',
            'descriptions' => [
                'Misconfiguration of VLAN settings on the %asset_type% at %location% following last week\'s firmware upgrade. Affected users are being placed in an incorrect subnet.',
                'BGP route advertisement error detected on the %asset_type% at %location%. Traffic is being routed via a suboptimal path, causing 40% throughput degradation.',
                'DNS resolution failures on %asset_type% at %location% after a configuration change. Users can ping external IPs but cannot resolve domain names.',
                'Firewall ACL misconfiguration on the %asset_type% at %location% is blocking legitimate government portal traffic on port 443. HTTPS services inaccessible to users.',
            ],
        ],
        [
            'type' => 'equipment_theft',
            'descriptions' => [
                'The entire %asset_type% unit at %location% has been stolen. The mounting bracket and all cabling have been removed. Cabinet was broken into overnight. Police report filed.',
                'Router and switch equipment at %location% OGN site stolen over the weekend. CCTV footage has been secured and forwarded to the Directorate of Criminal Investigations.',
                'SFP transceivers and patch cables stolen from the %asset_type% cabinet at %location%. The perpetrators appear to have had knowledge of the equipment layout.',
            ],
        ],
    ];

    private array $reporterNames = [
        'John Kamau', 'Grace Wanjiru', 'Peter Ochieng', 'Mary Akinyi', 'David Mutua',
        'Alice Njeri', 'James Otieno', 'Fatuma Hassan', 'Samuel Kipchoge', 'Esther Wambui',
        'Robert Mwangi', 'Priscilla Awuor', 'Daniel Kariuki', 'Janet Chebet', 'Kevin Omondi',
        'Caroline Muthoni', 'Patrick Kimani', 'Beatrice Wairimu', 'Stephen Ochola', 'Ruth Nekesa',
        'Joseph Njoroge', 'Elizabeth Auma', 'Charles Rotich', 'Lucy Wangui', 'Francis Maina',
        'Violet Adhiambo', 'Benjamin Chege', 'Mercy Wanjiku', 'Timothy Limo', 'Susan Nduta',
        'Hassan Abdullahi', 'Amina Warsame', 'Omar Sheikh', 'Saida Farah', 'Ibrahim Abdi',
        'Nadia Yusuf', 'Ali Hussein', 'Khadija Mohamed', 'Abdi Halane', 'Layla Duale',
    ];

    private array $activityComments = [
        'status_change' => [],
        'comment' => [
            'Issue logged and assigned priority status. Awaiting field assessment.',
            'Field officer dispatched to site. ETA approximately 2 hours.',
            'Remote diagnostics completed — power issue confirmed. KPLC contacted for restoration ETA.',
            'Coordinating with ISP upstream team. They have escalated internally and promise resolution within 6 hours.',
            'Site visit completed. Equipment physically inspected. Replacement parts ordered from supplier in Nairobi.',
            'Replacement unit procured from ICTA stores. Field installation scheduled for tomorrow morning.',
            'Spare parts have arrived from the Nairobi warehouse. Installation team will travel to site today.',
            'Third-party contractor (KenRen Cables Ltd) has been engaged to replace the cut fiber segment.',
            'KPLC has confirmed power restoration. Equipment rebooted and connectivity confirmed operational.',
            'Configuration rollback completed. Services restored. Monitoring for 24 hours before closing.',
            'Escalated to senior engineer for review. Complex routing issue beyond first-line resolution.',
            'Police OB number obtained: OB/456/2026. Case reference shared with ICTA security team.',
            'Vendor support ticket raised with Huawei Kenya. Reference: HW-2026-KE-00847.',
            'User community informed via SMS broadcast about the outage and estimated restoration time.',
            'Workaround implemented — traffic rerouted through the backup link. 60% capacity restored.',
            'Final testing completed. All services confirmed operational. Notifying affected users.',
            'Site visit deferred due to flooding on access road. Rescheduling for when conditions improve.',
            'Insurance claim initiated for stolen equipment. Claim reference: ICTA-INS-2026-0033.',
            'Root cause traced to firmware bug in version 8.4.2. Vendor patch being tested on lab unit.',
            'RICTO notified per escalation protocol. Director briefed at 15:00 today.',
        ],
        'field_note' => [
            'On-site observation: Equipment cabinet is weather-sealed and intact. No signs of physical intrusion.',
            'Field note: Power supply voltage measured at 180V — below the 220V rated input. Possible KPLC under-voltage issue.',
            'On-site check: Fiber splice at joint box #3 appears burnt. Suspect lightning surge through the cable run.',
            'Field observation: Antenna alignment has shifted — likely from last week\'s high winds. Realignment required.',
            'Physical inspection: Equipment cabinet is flooded at the base. Sandbags placed and drainage channel cleared.',
            'Field note: Local community members report the equipment was tampered with three nights ago at approximately 22:00.',
            'On-site: Signal strength measured at -78 dBm. Optimal is -65 dBm or better. Interference from adjacent ISP network detected.',
            'Field visit: UPS battery has swollen — must be replaced immediately. Fire risk assessed as low but non-zero.',
            'On-site: Link LED shows amber (degraded) rather than green (OK). Cable test shows 35% packet loss on Port 3.',
            'Field note: Site generator serviced and fuel topped up. Runtime now estimated at 72 hours from current level.',
            'Physical check: Vandals bent the mounting pole. Unit is now tilted 30° from optimal orientation.',
            'Field observation: Cooling fan on the OGN switch is making grinding noise. Failure imminent. Scheduled for replacement.',
            'On-site: GPS coordinates confirmed and corrected in FOAMS — previous entry had 0.3° longitude error.',
            'Field note: Local RICTO liaison officer met on site. Provided updated contact numbers for community liaison.',
            'Physical inspection: All four cable runs are intact. RJ45 connectors are in good condition. Issue is upstream.',
        ],
        'assignment' => [
            'Issue assigned to ICTO officer for field assessment and resolution.',
            'Reassigned to senior field technician given complexity of the hardware fault.',
            'Assignment updated — original officer is on leave. Issue transferred to AICTO for continuity.',
            'Escalated and reassigned to RICTO for regional coordination and resource allocation.',
        ],
        'escalation' => [
            'Issue escalated to Director level — SLA breach imminent and third-party coordination required.',
            'Escalated to NOC for national-level coordination. Multiple sites in the region affected by the same upstream fault.',
            'Issue escalated — RICTO involved. Replacement equipment requires procurement approval above ICTO authority.',
            'Escalation raised: Security incident with suspected criminal activity. ICTA security team and DCI engaged.',
            'Escalated due to high public visibility. Local media have been enquiring about the service outage.',
        ],
    ];

    private array $resolutionRootCauses = [
        'Fiber optic cable severed by Kenya Urban Roads Authority (KURA) road rehabilitation works along the %location% stretch. The cable trench was not adequately marked in the as-built drawings shared with KURA.',
        'Kenya Power (KPLC) extended load-shedding caused UPS batteries to fully discharge. The UPS unit had not been serviced and battery capacity was at 40% of rated value.',
        'Vandalism — external antenna assembly stolen by unknown persons overnight. This is a recurring issue at public hotspot sites in peri-urban areas.',
        'Firmware upgrade to version 8.5.1 introduced a regression in the VLAN trunking module, causing traffic from SSID "ICTA-Free-WiFi" to be dropped at the distribution layer.',
        'Antenna alignment drift caused by extreme winds (>80 km/h gusts recorded by KMD) during the April long rains season. Link margin was insufficient to maintain service.',
        'Power surge from a nearby lightning strike destroyed the surge protector and the Ethernet ports on the access point. The site lacked a proper lightning arrestor on the tower.',
        'BGP route table corruption following a manual misconfiguration during an after-hours change. The route was incorrectly summarised, causing black-holing of user traffic.',
        'Physical equipment theft. Perpetrators broke into the steel cabinet and removed the router and all associated SFP transceivers. Police OB filed.',
        'Cooling system failure in the OGN equipment room. Ambient temperature rose to 42°C, triggering thermal shutdown of the core switch. AC unit compressor had failed.',
        'ISP upstream fiber cut between the PoP and the NOFBI aggregation node. The break occurred at a third-party manhole that was accessed by a drainage contractor.',
        'Configuration error introduced during routine maintenance — an incorrect ACL rule was applied that blocked HTTPS traffic on port 443 for the affected subnet.',
        'Solar panel array degraded to 35% efficiency after 4 years of operation without replacement. Insufficient charging caused repeated power failures at the remote site.',
        'DNS forwarder misconfiguration after a network infrastructure change. The primary DNS server IP was changed without updating the DHCP scope options on the local router.',
        'Physical corrosion of SFP module contacts due to salt-laden coastal air. Module failure caused repeated link flapping on the uplink port.',
        'Backhoe excavation for a water main installation by Nairobi City Water and Sewerage Company (NCWSC) severed the underground fiber duct at a depth of 1.2 metres.',
    ];

    private array $resolutionStepTemplates = [
        ['Dispatched field officer to site for physical inspection', 'Identified root cause as %cause%', 'Procured replacement %equipment% from ICTA central stores', 'Installed and configured replacement unit', 'Tested connectivity — throughput restored to baseline of %throughput% Mbps', 'Updated asset inventory record in FOAMS', 'Closed with 24-hour monitoring period'],
        ['Raised urgent ticket with ISP upstream team', 'ISP dispatched field crew to repair fiber break', 'Confirmed fiber splice completed at joint box #%number%', 'Rebooted all affected equipment in correct sequence', 'Verified end-to-end connectivity for all user connections', 'Documented incident and updated SLA compliance records'],
        ['Engaged Kenya Power (KPLC) for fault restoration', 'Generator fuel replenished as interim measure', 'KPLC restored mains power after %hours% hours', 'Equipment powered on and diagnostics run', 'All services confirmed operational', 'UPS battery replacement recommended — scheduled for next maintenance cycle'],
        ['Rolled back firmware to previous stable version %version%', 'Verified service restoration after rollback', 'Raised vendor support ticket for firmware defect — reference %ref%', 'Applied vendor-provided hotfix patch in test environment', 'Deployed hotfix to production during next maintenance window', 'Monitored for 48 hours — no recurrence'],
        ['Filed police report — OB number %ob%', 'Obtained CCTV footage from adjacent building (where available)', 'Procured replacement equipment through emergency procurement', 'Installed and commissioned replacement unit', 'Upgraded physical security (padlock rating, cable management) at site', 'Submitted insurance claim for stolen/damaged equipment'],
    ];

    // ─── Public entry point ────────────────────────────────────────────────────

    public function run(): void
    {
        $counties = County::all();
        $users = User::with('roles')->get();

        if ($counties->isEmpty() || $users->isEmpty()) {
            $this->command->warn('Run DatabaseSeeder first — regions, counties, and users must exist.');

            return;
        }

        $allStaff = $users->filter(fn ($u) => $u->hasAnyRole(['admin', 'noc', 'ricto', 'icto', 'aicto']))->values();
        $fieldUsers = $users->filter(fn ($u) => $u->hasAnyRole(['ricto', 'icto', 'aicto']))->values();
        $nocAdminUsers = $users->filter(fn ($u) => $u->hasAnyRole(['noc', 'admin']))->values();

        // Fall back so the seeder doesn't crash if specific roles aren't seeded yet
        if ($allStaff->isEmpty()) {
            $allStaff = $users;
        }
        if ($fieldUsers->isEmpty()) {
            $fieldUsers = $users;
        }
        if ($nocAdminUsers->isEmpty()) {
            $nocAdminUsers = $users;
        }

        $this->command->info('Seeding 65 assets...');
        $assets = $this->seedAssets($counties, $fieldUsers);

        $this->command->info('Seeding 500 issues...');
        $issues = $this->seedIssues($assets, $counties, $users, $fieldUsers, $nocAdminUsers);

        $this->command->info('Seeding ~3,000 issue activities...');
        $this->seedIssueActivities($issues, $allStaff, $fieldUsers, $nocAdminUsers);

        $resolvedIssues = $issues->filter(
            fn ($i) => in_array($i->status->value, [IssueStatus::Resolved->value, IssueStatus::Closed->value])
        )->values();

        $this->command->info("Seeding resolutions for {$resolvedIssues->count()} resolved/closed issues...");
        $this->seedResolutions($resolvedIssues, $fieldUsers);

        $this->command->info('Seeding 4,000 notifications...');
        $this->seedNotifications($users, $issues);

        $this->command->info('Demo data seeding complete.');
        $this->command->line("  Assets:           {$assets->count()}");
        $this->command->line("  Issues:           {$issues->count()}");
        $this->command->line('  Activities:       '.IssueActivity::count());
        $this->command->line("  Resolutions:      {$resolvedIssues->count()} (one per resolved/closed issue)");
        $this->command->line('  Notifications:    '.DB::table('notifications')->count());
    }

    // ─── Assets ───────────────────────────────────────────────────────────────

    private function seedAssets(Collection $counties, Collection $fieldUsers): Collection
    {
        $existingCodes = Asset::pluck('asset_code')->flip();
        $countyByName = $counties->keyBy('name');

        foreach ($this->assetDefinitions as $def) {
            if ($existingCodes->has($def['code'])) {
                continue;
            }

            // Find county by name; fall back to random county if not seeded
            $county = $countyByName->get($def['county_name']) ?? $counties->random();

            Asset::create([
                'asset_code' => $def['code'],
                'name' => $def['name'],
                'type' => $def['type'],
                'county_id' => $county->id,
                'location_name' => $def['location'],
                'latitude' => $def['lat'],
                'longitude' => $def['lon'],
                'assigned_to' => $fieldUsers->isNotEmpty() ? $fieldUsers->random()->id : null,
                'installation_date' => $def['installed'],
                'manufacturer' => $def['manufacturer'],
                'model' => $def['model'],
                'serial_number' => $def['sn'],
                'status' => $def['status'],
            ]);
        }

        return Asset::all();
    }

    // ─── Issues ───────────────────────────────────────────────────────────────

    private function seedIssues(
        Collection $assets,
        Collection $counties,
        Collection $users,
        Collection $fieldUsers,
        Collection $nocAdminUsers,
    ): Collection {
        // Status distribution (total = 500), weighted toward terminal states for realism
        // in a system that has been running since 2019
        $statusDistribution = [
            IssueStatus::New->value => 10,
            IssueStatus::Acknowledged->value => 10,
            IssueStatus::InProgress->value => 25,
            IssueStatus::PendingThirdParty->value => 10,
            IssueStatus::Escalated->value => 10,
            IssueStatus::Resolved->value => 200,
            IssueStatus::Closed->value => 230,
            IssueStatus::Duplicate->value => 5,
        ]; // Total: 500

        $severityWeights = [
            IssueSeverity::Low->value => 40,
            IssueSeverity::Medium->value => 35,
            IssueSeverity::High->value => 15,
            IssueSeverity::Critical->value => 10,
        ];

        // SLA resolve hours per severity (mirrors sla_configurations)
        $slaResolveHours = [
            IssueSeverity::Low->value => 72,
            IssueSeverity::Medium->value => 24,
            IssueSeverity::High->value => 8,
            IssueSeverity::Critical->value => 4,
        ];

        // Determine next reference number sequence
        $maxSeq = Issue::query()
            ->where('reference_number', 'LIKE', 'ISS-%')
            ->selectRaw('MAX(CAST(SUBSTRING(reference_number, 5) AS UNSIGNED)) as max_seq')
            ->value('max_seq') ?? 0;

        $seq = (int) $maxSeq;
        $now = Carbon::now();

        foreach ($statusDistribution as $statusValue => $count) {
            for ($i = 0; $i < $count; $i++) {
                $seq++;
                $refNumber = 'ISS-'.str_pad($seq, 4, '0', STR_PAD_LEFT);

                $severity = $this->weightedRandom($severityWeights);
                $issueType = $this->randomIssuePool();
                $asset = $assets->random();
                $county = $counties->random();
                $reporter = $this->randomElement($this->reporterNames);
                $createdAt = $this->randomCreatedAt($statusValue, $now);
                $slaDueAt = $createdAt->copy()->addHours($slaResolveHours[$severity]);

                $acknowledged = null;
                $resolved = null;
                $closed = null;
                $escalatedAt = null;
                $escalatedByUserId = null;
                $isEscalated = false;
                $slaBreached = $slaDueAt->lt($now) && ! in_array($statusValue, [IssueStatus::Resolved->value, IssueStatus::Closed->value, IssueStatus::Duplicate->value]);
                $assignedUserId = null;
                $workaroundApplied = false;
                $duplicateOfId = null;

                $createdByUser = $users->random();
                $reporterCategory = $this->randomReporterCategory($createdByUser);

                switch ($statusValue) {
                    case IssueStatus::New->value:
                        break;

                    case IssueStatus::Acknowledged->value:
                        $acknowledged = $createdAt->copy()->addMinutes(rand(15, 120));
                        break;

                    case IssueStatus::InProgress->value:
                        $acknowledged = $createdAt->copy()->addMinutes(rand(15, 120));
                        $assignedUserId = $fieldUsers->isNotEmpty() ? $fieldUsers->random()->id : null;
                        break;

                    case IssueStatus::PendingThirdParty->value:
                        $acknowledged = $createdAt->copy()->addMinutes(rand(15, 90));
                        $assignedUserId = $fieldUsers->isNotEmpty() ? $fieldUsers->random()->id : null;
                        $workaroundApplied = (bool) rand(0, 1);
                        break;

                    case IssueStatus::Escalated->value:
                        $acknowledged = $createdAt->copy()->addMinutes(rand(10, 60));
                        $assignedUserId = $fieldUsers->isNotEmpty() ? $fieldUsers->random()->id : null;
                        $isEscalated = true;
                        $escalatedAt = $acknowledged->copy()->addHours(rand(2, 12));
                        $escalatedByUserId = $nocAdminUsers->isNotEmpty() ? $nocAdminUsers->random()->id : null;
                        $slaBreached = true;
                        break;

                    case IssueStatus::Resolved->value:
                        $ackMinutes = rand(10, 60);
                        $acknowledged = $createdAt->copy()->addMinutes($ackMinutes);
                        $assignedUserId = $fieldUsers->isNotEmpty() ? $fieldUsers->random()->id : null;
                        $resolved = $acknowledged->copy()->addHours(rand(1, (int) ($slaResolveHours[$severity] * 0.9)));
                        $slaBreached = $resolved->gt($slaDueAt);
                        $workaroundApplied = rand(0, 3) === 0; // 25% chance
                        break;

                    case IssueStatus::Closed->value:
                        $ackMinutes = rand(10, 60);
                        $acknowledged = $createdAt->copy()->addMinutes($ackMinutes);
                        $assignedUserId = $fieldUsers->isNotEmpty() ? $fieldUsers->random()->id : null;
                        $resolved = $acknowledged->copy()->addHours(rand(1, (int) ($slaResolveHours[$severity] * 0.9)));
                        $closed = $resolved->copy()->addHours(rand(24, 72));
                        $slaBreached = $resolved->gt($slaDueAt);
                        $workaroundApplied = rand(0, 3) === 0;
                        break;

                    case IssueStatus::Duplicate->value:
                        $acknowledged = $createdAt->copy()->addMinutes(rand(5, 30));
                        // Will link to a random earlier issue — handled after creation
                        break;
                }

                Issue::create([
                    'reference_number' => $refNumber,
                    'asset_id' => $asset->id,
                    'county_id' => $county->id,
                    'issue_type' => $issueType['type'],
                    'severity' => $severity,
                    'status' => $statusValue,
                    'reporter_category' => $reporterCategory,
                    'reporter_name' => $reporter,
                    'reporter_email' => strtolower(str_replace(' ', '.', $reporter)).'@example.co.ke',
                    'reporter_phone' => '+2547'.rand(10000000, 99999999),
                    'created_by_user_id' => $createdByUser->id,
                    'assigned_to_user_id' => $assignedUserId,
                    'description' => $this->buildDescription($issueType, $asset),
                    'workaround_applied' => $workaroundApplied,
                    'duplicate_of_id' => $duplicateOfId,
                    'acknowledged_at' => $acknowledged,
                    'resolved_at' => $resolved,
                    'closed_at' => $closed,
                    'sla_due_at' => $slaDueAt,
                    'sla_breached' => $slaBreached,
                    'is_escalated' => $isEscalated,
                    'escalated_at' => $escalatedAt,
                    'escalated_by_user_id' => $escalatedByUserId,
                    'created_at' => $createdAt,
                    'updated_at' => $resolved ?? $closed ?? $escalatedAt ?? $acknowledged ?? $createdAt,
                ]);
            }
        }

        // Link duplicate issues to existing resolved/closed ones
        $duplicates = Issue::where('status', IssueStatus::Duplicate->value)->get();
        $linkableIssues = Issue::whereIn('status', [IssueStatus::Resolved->value, IssueStatus::Closed->value])
            ->inRandomOrder()
            ->limit($duplicates->count())
            ->get();

        foreach ($duplicates as $index => $duplicate) {
            if ($linkableIssues->get($index)) {
                $duplicate->update(['duplicate_of_id' => $linkableIssues->get($index)->id]);
            }
        }

        return Issue::all();
    }

    // ─── Issue Activities ─────────────────────────────────────────────────────

    private function seedIssueActivities(
        Collection $issues,
        Collection $allStaff,
        Collection $fieldUsers,
        Collection $nocAdminUsers,
    ): void {
        $activities = [];

        foreach ($issues as $issue) {
            $activities = array_merge($activities, $this->buildActivitiesForIssue(
                $issue, $allStaff, $fieldUsers, $nocAdminUsers
            ));
        }

        // Pad to reach ~3000 by adding extra comments on random issues
        $current = count($activities);
        $target = 3000;
        if ($current < $target) {
            $padding = $target - $current;
            $resolvedIssues = $issues->filter(
                fn ($i) => in_array($i->status->value, [IssueStatus::Resolved->value, IssueStatus::Closed->value])
            )->values();

            for ($i = 0; $i < $padding; $i++) {
                $issue = $resolvedIssues->isNotEmpty() ? $resolvedIssues->random() : $issues->random();
                $user = $allStaff->isNotEmpty() ? $allStaff->random() : null;
                $activities[] = [
                    'issue_id' => $issue->id,
                    'user_id' => $user?->id,
                    'action_type' => 'comment',
                    'previous_status' => null,
                    'new_status' => null,
                    'comment' => $this->randomElement($this->activityComments['comment']),
                    'is_internal' => rand(0, 3) === 0,
                    'created_at' => $issue->created_at->copy()->addHours(rand(1, 48)),
                ];
            }
        }

        // Bulk insert in chunks to avoid memory issues
        foreach (array_chunk($activities, 200) as $chunk) {
            IssueActivity::insert($chunk);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildActivitiesForIssue(
        Issue $issue,
        Collection $allStaff,
        Collection $fieldUsers,
        Collection $nocAdminUsers,
    ): array {
        $activities = [];
        $user = $allStaff->isNotEmpty() ? $allStaff->random() : null;
        $fieldUser = $fieldUsers->isNotEmpty() ? $fieldUsers->random() : $user;
        $nocUser = $nocAdminUsers->isNotEmpty() ? $nocAdminUsers->random() : $user;
        $base = $issue->created_at;
        $status = $issue->status->value;

        $addActivity = function (string $actionType, ?string $prevStatus, ?string $newStatus, ?string $comment, bool $internal, CarbonInterface $at, ?User $actingUser) use (&$activities): void {
            $activities[] = [
                'issue_id' => $actingUser ? $actingUser->issue_id ?? null : null, // placeholder
                'user_id' => $actingUser?->id,
                'action_type' => $actionType,
                'previous_status' => $prevStatus,
                'new_status' => $newStatus,
                'comment' => $comment,
                'is_internal' => $internal,
                'created_at' => $at->toDateTimeString(),
            ];
        };

        // We'll build a flat array and replace issue_id below
        $addEntry = function (string $type, ?string $prev, ?string $next, ?string $comment, bool $internal, CarbonInterface $at, $actingUser) use ($issue, &$activities): void {
            $activities[] = [
                'issue_id' => $issue->id,
                'user_id' => $actingUser?->id,
                'action_type' => $type,
                'previous_status' => $prev,
                'new_status' => $next,
                'comment' => $comment,
                'is_internal' => $internal,
                'created_at' => $at->toDateTimeString(),
            ];
        };

        switch ($status) {
            case IssueStatus::New->value:
                $addEntry('comment', null, null, 'Issue received and logged into FOAMS. Awaiting initial assessment.', false, $base->copy()->addMinutes(rand(1, 5)), $user);
                if (rand(0, 1)) {
                    $addEntry('comment', null, null, $this->randomElement($this->activityComments['comment']), true, $base->copy()->addMinutes(rand(5, 30)), $nocUser);
                }
                break;

            case IssueStatus::Acknowledged->value:
                $ackAt = $base->copy()->addMinutes(rand(10, 60));
                $addEntry('status_change', IssueStatus::New->value, IssueStatus::Acknowledged->value, null, false, $ackAt, $nocUser);
                $addEntry('comment', null, null, 'Issue acknowledged. Field assessment being arranged.', false, $ackAt->copy()->addMinutes(rand(5, 30)), $nocUser);
                if (rand(0, 1)) {
                    $addEntry('comment', null, null, $this->randomElement($this->activityComments['comment']), true, $ackAt->copy()->addHours(rand(1, 4)), $user);
                }
                break;

            case IssueStatus::InProgress->value:
                $ackAt = $base->copy()->addMinutes(rand(10, 60));
                $progressAt = $ackAt->copy()->addHours(rand(1, 4));
                $addEntry('status_change', IssueStatus::New->value, IssueStatus::Acknowledged->value, null, false, $ackAt, $nocUser);
                $addEntry('assignment', null, null, $this->randomElement($this->activityComments['assignment']), false, $ackAt->copy()->addMinutes(rand(5, 20)), $nocUser);
                $addEntry('status_change', IssueStatus::Acknowledged->value, IssueStatus::InProgress->value, null, false, $progressAt, $fieldUser);
                $addEntry('field_note', null, null, $this->randomElement($this->activityComments['field_note']), false, $progressAt->copy()->addHours(rand(1, 6)), $fieldUser);
                if (rand(0, 1)) {
                    $addEntry('comment', null, null, $this->randomElement($this->activityComments['comment']), true, $progressAt->copy()->addHours(rand(2, 8)), $nocUser);
                }
                break;

            case IssueStatus::PendingThirdParty->value:
                $ackAt = $base->copy()->addMinutes(rand(10, 60));
                $progressAt = $ackAt->copy()->addHours(rand(1, 3));
                $pendingAt = $progressAt->copy()->addHours(rand(2, 8));
                $addEntry('status_change', IssueStatus::New->value, IssueStatus::Acknowledged->value, null, false, $ackAt, $nocUser);
                $addEntry('assignment', null, null, $this->randomElement($this->activityComments['assignment']), false, $ackAt->copy()->addMinutes(rand(5, 20)), $nocUser);
                $addEntry('status_change', IssueStatus::Acknowledged->value, IssueStatus::InProgress->value, null, false, $progressAt, $fieldUser);
                $addEntry('field_note', null, null, $this->randomElement($this->activityComments['field_note']), false, $progressAt->copy()->addHours(rand(1, 4)), $fieldUser);
                $addEntry('status_change', IssueStatus::InProgress->value, IssueStatus::PendingThirdParty->value, 'Awaiting third-party contractor for fiber repair. KenRen Cables Ltd engaged.', false, $pendingAt, $fieldUser);
                $addEntry('comment', null, null, $this->randomElement($this->activityComments['comment']), true, $pendingAt->copy()->addHours(rand(2, 12)), $nocUser);
                break;

            case IssueStatus::Escalated->value:
                $ackAt = $base->copy()->addMinutes(rand(10, 45));
                $progressAt = $ackAt->copy()->addHours(rand(1, 3));
                $escalateAt = $progressAt->copy()->addHours(rand(4, 12));
                $addEntry('status_change', IssueStatus::New->value, IssueStatus::Acknowledged->value, null, false, $ackAt, $nocUser);
                $addEntry('assignment', null, null, $this->randomElement($this->activityComments['assignment']), false, $ackAt->copy()->addMinutes(rand(5, 20)), $nocUser);
                $addEntry('status_change', IssueStatus::Acknowledged->value, IssueStatus::InProgress->value, null, false, $progressAt, $fieldUser);
                $addEntry('field_note', null, null, $this->randomElement($this->activityComments['field_note']), false, $progressAt->copy()->addHours(rand(1, 3)), $fieldUser);
                $addEntry('comment', null, null, $this->randomElement($this->activityComments['comment']), true, $progressAt->copy()->addHours(rand(2, 6)), $fieldUser);
                $addEntry('escalation', IssueStatus::InProgress->value, IssueStatus::Escalated->value, $this->randomElement($this->activityComments['escalation']), false, $escalateAt, $nocUser);
                $addEntry('comment', null, null, 'Director notified by email and phone. Awaiting senior management decision on resource allocation.', true, $escalateAt->copy()->addMinutes(rand(10, 30)), $nocUser);
                break;

            case IssueStatus::Resolved->value:
                $ackAt = $base->copy()->addMinutes(rand(10, 60));
                $progressAt = $ackAt->copy()->addHours(rand(1, 4));
                $resolvedAt = $issue->resolved_at ?? $progressAt->copy()->addHours(rand(2, 12));
                $addEntry('status_change', IssueStatus::New->value, IssueStatus::Acknowledged->value, null, false, $ackAt, $nocUser);
                $addEntry('assignment', null, null, $this->randomElement($this->activityComments['assignment']), false, $ackAt->copy()->addMinutes(rand(5, 20)), $nocUser);
                $addEntry('status_change', IssueStatus::Acknowledged->value, IssueStatus::InProgress->value, null, false, $progressAt, $fieldUser);
                $addEntry('field_note', null, null, $this->randomElement($this->activityComments['field_note']), false, $progressAt->copy()->addHours(rand(1, 3)), $fieldUser);
                $addEntry('comment', null, null, $this->randomElement($this->activityComments['comment']), false, $progressAt->copy()->addHours(rand(2, 5)), $fieldUser);
                if (rand(0, 1)) {
                    $addEntry('comment', null, null, $this->randomElement($this->activityComments['comment']), true, $progressAt->copy()->addHours(rand(3, 7)), $nocUser);
                }
                $addEntry('status_change', IssueStatus::InProgress->value, IssueStatus::Resolved->value, 'Root cause identified and resolved. Equipment restored to full operational status. User connectivity confirmed.', false, $resolvedAt, $fieldUser);
                break;

            case IssueStatus::Closed->value:
                $ackAt = $base->copy()->addMinutes(rand(10, 60));
                $progressAt = $ackAt->copy()->addHours(rand(1, 4));
                $resolvedAt = $issue->resolved_at ?? $progressAt->copy()->addHours(rand(2, 12));
                $closedAt = $issue->closed_at ?? $resolvedAt->copy()->addHours(rand(24, 72));
                $addEntry('status_change', IssueStatus::New->value, IssueStatus::Acknowledged->value, null, false, $ackAt, $nocUser);
                $addEntry('assignment', null, null, $this->randomElement($this->activityComments['assignment']), false, $ackAt->copy()->addMinutes(rand(5, 20)), $nocUser);
                $addEntry('status_change', IssueStatus::Acknowledged->value, IssueStatus::InProgress->value, null, false, $progressAt, $fieldUser);
                $addEntry('field_note', null, null, $this->randomElement($this->activityComments['field_note']), false, $progressAt->copy()->addHours(rand(1, 3)), $fieldUser);
                $addEntry('comment', null, null, $this->randomElement($this->activityComments['comment']), false, $progressAt->copy()->addHours(rand(2, 5)), $fieldUser);
                if (rand(0, 2) !== 0) {
                    $addEntry('comment', null, null, $this->randomElement($this->activityComments['comment']), true, $progressAt->copy()->addHours(rand(3, 8)), $nocUser);
                }
                $addEntry('status_change', IssueStatus::InProgress->value, IssueStatus::Resolved->value, 'Equipment fully restored. Monitoring period initiated.', false, $resolvedAt, $fieldUser);
                $addEntry('status_change', IssueStatus::Resolved->value, IssueStatus::Closed->value, 'Issue verified as fully resolved after 24-hour monitoring period. Closing.', false, $closedAt, $nocUser);
                break;

            case IssueStatus::Duplicate->value:
                $ackAt = $base->copy()->addMinutes(rand(5, 30));
                $addEntry('comment', null, null, 'Reviewing for duplication against existing open issues.', true, $ackAt, $nocUser);
                $addEntry('status_change', IssueStatus::New->value, IssueStatus::Duplicate->value, 'This issue is a duplicate of an existing open case. Merging and closing.', false, $ackAt->copy()->addMinutes(rand(5, 20)), $nocUser);
                break;
        }

        return $activities;
    }

    // ─── Resolutions ──────────────────────────────────────────────────────────

    private function seedResolutions(Collection $resolvedIssues, Collection $fieldUsers): void
    {
        $existingIssueIds = Resolution::pluck('issue_id')->flip();

        foreach ($resolvedIssues as $issue) {
            if ($existingIssueIds->has($issue->id)) {
                continue;
            }

            $resolvedByUser = $fieldUsers->isNotEmpty() ? $fieldUsers->random() : null;
            $rootCause = $this->randomElement($this->resolutionRootCauses);
            $rootCause = str_replace('%location%', $issue->asset?->location_name ?? 'the affected site', $rootCause);

            $stepsTemplate = $this->randomElement($this->resolutionStepTemplates);
            $steps = array_map(fn ($step) => $this->fillStepPlaceholders($step, $issue->asset), $stepsTemplate);

            $resolutionType = rand(0, 2) === 0 ? ResolutionType::Temporary : ResolutionType::Permanent;

            Resolution::create([
                'issue_id' => $issue->id,
                'root_cause' => $rootCause,
                'steps_taken' => $steps,
                'resolution_type' => $resolutionType,
                'resolved_by_user_id' => $resolvedByUser?->id,
                'resolved_at' => $issue->resolved_at ?? $issue->updated_at,
                'created_at' => $issue->resolved_at ?? $issue->updated_at,
                'updated_at' => $issue->resolved_at ?? $issue->updated_at,
            ]);
        }
    }

    // ─── Notifications ────────────────────────────────────────────────────────

    private function seedNotifications(Collection $users, Collection $issues): void
    {
        $notificationTypes = [
            'App\\Notifications\\IssueCreatedNotification' => 30,
            'App\\Notifications\\IssueStatusChangedNotification' => 25,
            'App\\Notifications\\SlaWarningNotification' => 15,
            'App\\Notifications\\SlaBreachNotification' => 10,
            'App\\Notifications\\IssueEscalatedNotification' => 10,
            'App\\Notifications\\DailyStatusReminderNotification' => 10,
        ];

        $notificationMessages = [
            'App\\Notifications\\IssueCreatedNotification' => [
                ['title' => 'New Issue Reported', 'message' => 'A new %severity% severity issue (%ref%) has been reported at %location%.'],
                ['title' => 'Issue Submitted', 'message' => 'Issue %ref% (%type%) has been submitted and is awaiting assignment.'],
                ['title' => 'Critical Issue Alert', 'message' => 'CRITICAL: Issue %ref% reported at %location%. Immediate attention required.'],
            ],
            'App\\Notifications\\IssueStatusChangedNotification' => [
                ['title' => 'Issue Status Updated', 'message' => 'Issue %ref% status changed to %status%. Please review.'],
                ['title' => 'Issue Progressing', 'message' => 'Issue %ref% at %location% is now in progress. Field officer dispatched.'],
                ['title' => 'Issue Resolved', 'message' => 'Issue %ref% has been marked as resolved. Please verify and close.'],
            ],
            'App\\Notifications\\SlaWarningNotification' => [
                ['title' => 'SLA Warning — 50% Elapsed', 'message' => 'Issue %ref% (%severity%) is at 50%% of SLA time. Due at %due%.'],
                ['title' => 'SLA Time Running Out', 'message' => 'Warning: Issue %ref% SLA deadline approaching. Immediate action required.'],
            ],
            'App\\Notifications\\SlaBreachNotification' => [
                ['title' => 'SLA BREACHED', 'message' => 'Issue %ref% has breached its SLA target. Immediate escalation required.'],
                ['title' => 'SLA Breach Alert', 'message' => 'BREACH: Issue %ref% at %location% exceeded the %severity% SLA threshold.'],
            ],
            'App\\Notifications\\IssueEscalatedNotification' => [
                ['title' => 'Issue Escalated to You', 'message' => 'Issue %ref% has been escalated and requires your attention as a senior officer.'],
                ['title' => 'Escalation Notice', 'message' => 'Issue %ref% (%severity% severity) at %location% has been escalated. Please review.'],
            ],
            'App\\Notifications\\DailyStatusReminderNotification' => [
                ['title' => 'Daily Status Log Reminder', 'message' => 'Reminder: You have not submitted a status log for %count% asset(s) today. Please update before 18:00 EAT.'],
                ['title' => 'Status Log Due', 'message' => 'Daily reminder: Please log the operational status of your assigned assets before close of business today.'],
            ],
        ];

        $rows = [];
        $target = 4000;
        $generated = 0;
        $now = Carbon::now();

        while ($generated < $target) {
            $user = $users->random();
            $type = $this->weightedRandom($notificationTypes);
            $issue = $issues->isNotEmpty() ? $issues->random() : null;

            $messageTemplates = $notificationMessages[$type];
            $template = $this->randomElement($messageTemplates);
            $createdAt = $now->copy()->subDays(rand(0, 180))->subHours(rand(0, 23));

            $data = [
                'title' => $template['title'],
                'message' => $this->fillNotificationMessage($template['message'], $issue),
                'type' => $type,
                'created_at' => $createdAt->toDateTimeString(),
            ];

            if ($issue) {
                $data['issue_id'] = $issue->id;
                $data['reference_number'] = $issue->reference_number;
                $data['severity'] = $issue->severity->value;
            }

            $rows[] = [
                'id' => Str::uuid()->toString(),
                'type' => $type,
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $user->id,
                'data' => json_encode($data),
                'read_at' => rand(0, 2) !== 0 ? $createdAt->copy()->addMinutes(rand(5, 1440))->toDateTimeString() : null, // ~67% read
                'created_at' => $createdAt->toDateTimeString(),
                'updated_at' => $createdAt->toDateTimeString(),
            ];

            $generated++;

            // Bulk insert every 500 rows to manage memory
            if (count($rows) >= 500) {
                DB::table('notifications')->insert($rows);
                $rows = [];
            }
        }

        if (! empty($rows)) {
            DB::table('notifications')->insert($rows);
        }
    }

    // ─── Helper methods ───────────────────────────────────────────────────────

    private function randomCreatedAt(string $status, Carbon $now): CarbonInterface
    {
        // Older issues for terminal statuses, newer for open ones
        return match ($status) {
            IssueStatus::Closed->value => $now->copy()->subDays(rand(30, 730)),
            IssueStatus::Resolved->value => $now->copy()->subDays(rand(7, 365)),
            IssueStatus::Duplicate->value => $now->copy()->subDays(rand(14, 200)),
            IssueStatus::Escalated->value => $now->copy()->subDays(rand(3, 60)),
            IssueStatus::PendingThirdParty->value => $now->copy()->subDays(rand(2, 30)),
            IssueStatus::InProgress->value => $now->copy()->subDays(rand(1, 14)),
            IssueStatus::Acknowledged->value => $now->copy()->subDays(rand(1, 7)),
            IssueStatus::New->value => $now->copy()->subDays(rand(0, 3)),
            default => $now->copy()->subDays(rand(1, 90)),
        };
    }

    private function randomReporterCategory(User $user): string
    {
        if ($user->hasAnyRole(['icto', 'aicto', 'ricto', 'noc', 'admin'])) {
            return ReporterCategory::FieldOfficer->value;
        }
        if ($user->hasRole('public_servant')) {
            return ReporterCategory::PublicServant->value;
        }

        return $this->randomElement([
            ReporterCategory::GeneralPublic->value,
            ReporterCategory::PublicServant->value,
            ReporterCategory::FieldOfficer->value,
        ]);
    }

    private function randomIssuePool(): array
    {
        return $this->randomElement($this->issuePool);
    }

    private function buildDescription(array $issueType, Asset $asset): string
    {
        $template = $this->randomElement($issueType['descriptions']);
        $locations = [
            $asset->location_name,
            $asset->name,
            $asset->county?->name ?? 'the county',
        ];

        return str_replace(
            ['%location%', '%asset_type%', '%count%', '%time%'],
            [
                $this->randomElement($locations),
                match ($asset->type) {
                    AssetType::WifiHotspot => 'WiFi hotspot',
                    AssetType::NofbiNode => 'NOFBI node',
                    AssetType::OgnEquipment => 'OGN equipment',
                },
                (string) rand(15, 300),
                rand(6, 23).':'.str_pad((string) (rand(0, 5) * 10), 2, '0', STR_PAD_LEFT).' EAT',
            ],
            $template,
        );
    }

    private function fillStepPlaceholders(string $step, ?Asset $asset): string
    {
        return str_replace(
            ['%cause%', '%equipment%', '%throughput%', '%number%', '%hours%', '%version%', '%ref%', '%ob%'],
            [
                'power failure',
                $asset?->model ?? 'access point',
                (string) rand(20, 100),
                (string) rand(1, 10),
                (string) rand(4, 24),
                '8.'.rand(3, 5).'.'.rand(0, 3),
                'HW-'.date('Y').'-KE-'.str_pad((string) rand(1, 9999), 5, '0', STR_PAD_LEFT),
                'OB/'.rand(100, 999).'/'.date('Y'),
            ],
            $step,
        );
    }

    private function fillNotificationMessage(string $template, ?Issue $issue): string
    {
        return str_replace(
            ['%ref%', '%severity%', '%location%', '%status%', '%type%', '%due%', '%count%'],
            [
                $issue?->reference_number ?? 'ISS-0000',
                $issue?->severity->value ?? 'medium',
                $issue?->asset?->location_name ?? 'the site',
                $issue?->status->value ?? 'in_progress',
                $issue?->issue_type ?? 'connectivity',
                $issue?->sla_due_at?->format('d M Y H:i') ?? 'N/A',
                (string) rand(1, 8),
            ],
            $template,
        );
    }

    /**
     * @param  array<string, int>  $weights
     */
    private function weightedRandom(array $weights): string
    {
        $total = array_sum($weights);
        $rand = rand(1, $total);
        $cumulative = 0;

        foreach ($weights as $value => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $value;
            }
        }

        return array_key_first($weights);
    }

    /**
     * @template T
     *
     * @param  list<T>  $array
     * @return T
     */
    private function randomElement(array $array): mixed
    {
        return $array[array_rand($array)];
    }
}
