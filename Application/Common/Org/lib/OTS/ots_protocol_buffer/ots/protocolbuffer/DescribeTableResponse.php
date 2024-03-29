<?php
namespace ots\protocolbuffer;

// @@protoc_insertion_point(namespace:.ots.protocolbuffer.DescribeTableResponse)

/**
 * Generated by the protocol buffer compiler.  DO NOT EDIT!
 * source: ots_protocol_buffer.proto
 *
 * -*- magic methods -*-
 *
 * @method \ots\protocolbuffer\TableMeta getTableMeta()
 * @method void setTableMeta(\ots\protocolbuffer\TableMeta $value)
 * @method \ots\protocolbuffer\ReservedThroughputDetails getReservedThroughputDetails()
 * @method void setReservedThroughputDetails(\ots\protocolbuffer\ReservedThroughputDetails $value)
 */
class DescribeTableResponse extends \ProtocolBuffers\Message
{
  // @@protoc_insertion_point(traits:.ots.protocolbuffer.DescribeTableResponse)
  
  /**
   * @var \ots\protocolbuffer\TableMeta $table_meta
   * @tag 1
   * @label required
   * @type \ProtocolBuffers::TYPE_MESSAGE
   **/
  protected $table_meta;
  
  /**
   * @var \ots\protocolbuffer\ReservedThroughputDetails $reserved_throughput_details
   * @tag 2
   * @label required
   * @type \ProtocolBuffers::TYPE_MESSAGE
   **/
  protected $reserved_throughput_details;
  
  
  // @@protoc_insertion_point(properties_scope:.ots.protocolbuffer.DescribeTableResponse)

  // @@protoc_insertion_point(class_scope:.ots.protocolbuffer.DescribeTableResponse)

  /**
   * get descriptor for protocol buffers
   * 
   * @return \ProtocolBuffersDescriptor
   */
  public static function getDescriptor()
  {
    static $descriptor;
    
    if (!isset($descriptor)) {
      $desc = new \ProtocolBuffers\DescriptorBuilder();
      $desc->addField(1, new \ProtocolBuffers\FieldDescriptor(array(
        "type"     => \ProtocolBuffers::TYPE_MESSAGE,
        "name"     => "table_meta",
        "required" => true,
        "optional" => false,
        "repeated" => false,
        "packable" => false,
        "default"  => null,
        "message" => '\ots\protocolbuffer\TableMeta',
      )));
      $desc->addField(2, new \ProtocolBuffers\FieldDescriptor(array(
        "type"     => \ProtocolBuffers::TYPE_MESSAGE,
        "name"     => "reserved_throughput_details",
        "required" => true,
        "optional" => false,
        "repeated" => false,
        "packable" => false,
        "default"  => null,
        "message" => '\ots\protocolbuffer\ReservedThroughputDetails',
      )));
      // @@protoc_insertion_point(builder_scope:.ots.protocolbuffer.DescribeTableResponse)

      $descriptor = $desc->build();
    }
    return $descriptor;
  }

}
