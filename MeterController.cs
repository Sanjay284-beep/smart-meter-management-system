using Microsoft.AspNetCore.Mvc;
using System;
using System.Collections.Generic;
using System.Data.SqlClient;

namespace SmartMeterAPI.Controllers
{
    [ApiController]
    [Route("api/[controller]")]
    public class MeterController : ControllerBase
    {
        private readonly string _connectionString = 
            "Server=localhost;Database=SmartMeterDB;Trusted_Connection=True;";

        // GET: api/meter/all
        [HttpGet("all")]
        public IActionResult GetAllMeters()
        {
            var meters = new List<Meter>();
            
            using (SqlConnection conn = new SqlConnection(_connectionString))
            {
                conn.Open();
                string query = @"SELECT MeterID, Location, ClientName, Status, 
                                LastReading, InstallationDate FROM Meters";
                
                using (SqlCommand cmd = new SqlCommand(query, conn))
                using (SqlDataReader reader = cmd.ExecuteReader())
                {
                    while (reader.Read())
                    {
                        meters.Add(new Meter
                        {
                            MeterID = reader.GetString(0),
                            Location = reader.GetString(1),
                            ClientName = reader.GetString(2),
                            Status = reader.GetString(3),
                            LastReading = reader.GetDecimal(4),
                            InstallationDate = reader.GetDateTime(5)
                        });
                    }
                }
            }
            
            return Ok(meters);
        }

        // POST: api/meter/add
        [HttpPost("add")]
        public IActionResult AddMeter([FromBody] Meter meter)
        {
            using (SqlConnection conn = new SqlConnection(_connectionString))
            {
                conn.Open();
                string query = @"INSERT INTO Meters 
                    (MeterID, Location, ClientName, Status, LastReading, InstallationDate)
                    VALUES (@MeterID, @Location, @ClientName, @Status, @LastReading, @InstallationDate)";
                
                using (SqlCommand cmd = new SqlCommand(query, conn))
                {
                    cmd.Parameters.AddWithValue("@MeterID", meter.MeterID);
                    cmd.Parameters.AddWithValue("@Location", meter.Location);
                    cmd.Parameters.AddWithValue("@ClientName", meter.ClientName);
                    cmd.Parameters.AddWithValue("@Status", meter.Status);
                    cmd.Parameters.AddWithValue("@LastReading", meter.LastReading);
                    cmd.Parameters.AddWithValue("@InstallationDate", meter.InstallationDate);
                    
                    cmd.ExecuteNonQuery();
                }
            }
            
            return Ok(new { message = "Meter added successfully" });
        }

        // GET: api/meter/reading/{meterId}
        [HttpGet("reading/{meterId}")]
        public IActionResult GetMeterReadings(string meterId)
        {
            var readings = new List<Reading>();
            
            using (SqlConnection conn = new SqlConnection(_connectionString))
            {
                conn.Open();
                string query = @"SELECT ReadingDate, Units, Status 
                    FROM MeterReadings WHERE MeterID = @MeterID 
                    ORDER BY ReadingDate DESC";
                
                using (SqlCommand cmd = new SqlCommand(query, conn))
                {
                    cmd.Parameters.AddWithValue("@MeterID", meterId);
                    using (SqlDataReader reader = cmd.ExecuteReader())
                    {
                        while (reader.Read())
                        {
                            readings.Add(new Reading
                            {
                                ReadingDate = reader.GetDateTime(0),
                                Units = reader.GetDecimal(1),
                                Status = reader.GetString(2)
                            });
                        }
                    }
                }
            }
            
            return Ok(readings);
        }

        // PUT: api/meter/update-status
        [HttpPut("update-status")]
        public IActionResult UpdateMeterStatus([FromBody] StatusUpdate update)
        {
            using (SqlConnection conn = new SqlConnection(_connectionString))
            {
                conn.Open();
                string query = "UPDATE Meters SET Status = @Status WHERE MeterID = @MeterID";
                
                using (SqlCommand cmd = new SqlCommand(query, conn))
                {
                    cmd.Parameters.AddWithValue("@Status", update.Status);
                    cmd.Parameters.AddWithValue("@MeterID", update.MeterID);
                    cmd.ExecuteNonQuery();
                }
            }
            
            return Ok(new { message = "Status updated successfully" });
        }
    }

    public class Meter
    {
        public string MeterID { get; set; }
        public string Location { get; set; }
        public string ClientName { get; set; }
        public string Status { get; set; }
        public decimal LastReading { get; set; }
        public DateTime InstallationDate { get; set; }
    }

    public class Reading
    {
        public DateTime ReadingDate { get; set; }
        public decimal Units { get; set; }
        public string Status { get; set; }
    }

    public class StatusUpdate
    {
        public string MeterID { get; set; }
        public string Status { get; set; }
    }
}